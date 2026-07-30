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
    ];

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

        $segmentRows = DB::select("
            SELECT
                COALESCE(s.segment_code, 'OT') AS segment_code,
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
                GROUP BY cb.cif
            ) m

            LEFT JOIN
            (
                -- Step 2: Classify each CIF into a segment
                -- Only CB*, CM*, CS* accounts cast a vote.
                -- DB*, NAP, BLANK/NULL accounts return NULL and are ignored.
                -- A CIF with no CB/CM/CS accounts gets NULL here,
                -- and COALESCE above assigns it to 'OT'.
                SELECT
                    x.cif,
                    CASE
                        WHEN SUM(CASE WHEN x.segment_code = 'CB' THEN 1 ELSE 0 END) > 0 THEN 'CB'
                        WHEN SUM(CASE WHEN x.segment_code = 'CM' THEN 1 ELSE 0 END) > 0 THEN 'CM'
                        WHEN SUM(CASE WHEN x.segment_code = 'CS' THEN 1 ELSE 0 END) > 0 THEN 'CS'
                        ELSE NULL
                    END AS segment_code
                FROM
                (
                    SELECT
                        f12_cif AS cif,
                        CASE
                            WHEN UPPER(TRIM(etibiseg2)) LIKE 'CB%' THEN 'CB'
                            WHEN UPPER(TRIM(etibiseg2)) LIKE 'CM%' THEN 'CM'
                            WHEN UPPER(TRIM(etibiseg2)) LIKE 'CS%' THEN 'CS'
                            ELSE NULL
                        END AS segment_code
                    FROM customer_accounts_imports
                    WHERE f12_cif IS NOT NULL
                      AND etibiseg2 IS NOT NULL
                      AND TRIM(etibiseg2) <> ''
                ) x
                -- Only keep rows that actually voted for a real segment
                WHERE x.segment_code IS NOT NULL
                GROUP BY x.cif
            ) s ON s.cif = m.cif

            GROUP BY COALESCE(s.segment_code, 'OT')
        ", array_merge(
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
