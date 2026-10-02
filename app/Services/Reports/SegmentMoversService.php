<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SegmentMoversService
{
    public const SEGMENT_MAP = [
        'CB'  => 'Corporate',
        'CM'  => 'Commercial',
        'CS'  => 'Consumer',
        'OT'  => 'Others',
        'ALL' => 'Totals',
    ];

    private const EXCLUDED_CR_GL = '216220001';

    private const INCLUDED_EXCEPTION_CIFS = [
        '470000068',
        '470218244',
        '470224763',
        '470090458',
        '470321717',
        '470291487',
        '470317567',
        '470803302',
        '470251434',
        '470130430',
    ];

    // Forces this CIF into Corporate (CB) regardless of its actual etibiseg2 classification
    // (or lack of one). Matches CIF_SEGMENT_OVERRIDES in WeeklySegmentReportService.
    private const CIF_SEGMENT_OVERRIDES = [
        '470130430' => 'CB',

        // TEMPORARY manual reclassification (Oct 2026) — revert once corrected at source.
        '471704700' => 'CM', // BLUE SKY ENERGY LIMITED → Commercial / Local Corporates (was Commercial / SME)
        '471650332' => 'CM', // MFI TECHNOLOGY SOLUTIONS LIMITED → Commercial / Local Corporates (was Commercial / SME)
        '471770982' => 'CB', // MASHONALAND TOBACCO COMPANY → Regional Corporates (was Unmapped)
    ];

    private function segmentOverrideCaseSql(string $cifColumn, string $fallbackExpr): string
    {
        $whens = implode(' ', array_fill(0, count(self::CIF_SEGMENT_OVERRIDES), "WHEN {$cifColumn} = ? THEN ?"));
        return "CASE {$whens} ELSE {$fallbackExpr} END";
    }

    private function segmentOverrideBindings(): array
    {
        $bindings = [];
        foreach (self::CIF_SEGMENT_OVERRIDES as $cif => $code) {
            $bindings[] = $cif;
            $bindings[] = $code;
        }
        return $bindings;
    }

    public function build(string $start, string $end): void
    {
        $startDate = Carbon::parse($start)->toDateString();
        $endDate   = Carbon::parse($end)->toDateString();

        foreach (['segment_movers', 'customer_balances', 'customer_accounts_imports'] as $table) {
            if (!Schema::hasTable($table)) {
                throw new \RuntimeException("{$table} table not found.");
            }
        }

        $this->safeDeleteExisting($startDate, $endDate);

        $now = now();

        $exceptionPlaceholders = implode(',', array_fill(0, count(self::INCLUDED_EXCEPTION_CIFS), '?'));
        $segCodeCase = $this->segmentOverrideCaseSql('m.cif', "COALESCE(s.segment_code, 'OT')");

        $segmentRows = DB::select("
            SELECT
                {$segCodeCase} AS segment_code,
                SUM(m.start_balance)            AS start_balance,
                SUM(m.end_balance)              AS end_balance,
                SUM(m.end_balance - m.start_balance) AS movement,
                COUNT(DISTINCT m.cif)           AS cif_count
            FROM
            (
                -- Step 1: Get start and end balance per CIF
                SELECT
                    cb.cif,

                    SUM(
                        CASE
                            WHEN cb.balance_date = ?
                            THEN GREATEST(cb.lcy_balance, 0)
                            ELSE 0
                        END
                    ) AS start_balance,

                    SUM(
                        CASE
                            WHEN cb.balance_date = ?
                            THEN GREATEST(cb.lcy_balance, 0)
                            ELSE 0
                        END
                    ) AS end_balance

                FROM customer_balances cb
                WHERE cb.balance_date IN (?, ?)
                  AND cb.cif IS NOT NULL
                  AND (
                        cb.cif IN ({$exceptionPlaceholders})
                        OR (
                            UPPER(TRIM(cb.branch_code)) <> 'P50'
                            AND (cb.cr_gl IS NULL OR cb.cr_gl <> ?)
                        )
                  )
                  AND " . StaffExclusion::depositSql('cb') . "
                GROUP BY cb.cif
            ) m

            LEFT JOIN
            (
                -- Step 2: Classify each CIF into a segment (mapping table first, MIS prefix
                -- fallback, Corporate > Commercial > Consumer) — see CifSegment.
                " . CifSegment::subquerySql() . "
            ) s ON s.cif = m.cif

            GROUP BY 1
        ", array_merge(
            $this->segmentOverrideBindings(),
            [
                $startDate,
                $endDate,
                $startDate,
                $endDate,
            ],
            self::INCLUDED_EXCEPTION_CIFS,
            [
                self::EXCLUDED_CR_GL,
            ]
        ));

        if (empty($segmentRows)) {
            return;
        }

        $final   = [];
        $totals  = [
            'start_balance' => 0.0,
            'end_balance'   => 0.0,
            'movement'      => 0.0,
            'cif_count'     => 0,
        ];

        foreach ($segmentRows as $row) {
            $code = strtoupper((string) ($row->segment_code ?? 'OT'));

            if (!isset(self::SEGMENT_MAP[$code]) || $code === 'ALL') {
                $code = 'OT';
            }

            $startBalance = (float) ($row->start_balance ?? 0);
            $endBalance   = (float) ($row->end_balance   ?? 0);
            $movement     = (float) ($row->movement      ?? 0);
            $cifCount     = (int)   ($row->cif_count     ?? 0);

            $final[] = [
                'start_date'    => $startDate,
                'end_date'      => $endDate,
                'segment_code'  => $code,
                'segment_name'  => self::SEGMENT_MAP[$code],
                'start_balance' => $startBalance,
                'end_balance'   => $endBalance,
                'movement'      => $movement,
                'cif_count'     => $cifCount,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];

            $totals['start_balance'] += $startBalance;
            $totals['end_balance']   += $endBalance;
            $totals['movement']      += $movement;
            $totals['cif_count']     += $cifCount;
        }

        $final[] = [
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'segment_code'  => 'ALL',
            'segment_name'  => self::SEGMENT_MAP['ALL'],
            'start_balance' => $totals['start_balance'],
            'end_balance'   => $totals['end_balance'],
            'movement'      => $totals['movement'],
            'cif_count'     => $totals['cif_count'],
            'created_at'    => $now,
            'updated_at'    => $now,
        ];

        DB::table('segment_movers')->insert($final);
    }

    private function safeDeleteExisting(string $startDate, string $endDate): void
    {
        DB::table('segment_movers')
            ->whereDate('start_date', $startDate)
            ->whereDate('end_date', $endDate)
            ->delete();
    }
}
