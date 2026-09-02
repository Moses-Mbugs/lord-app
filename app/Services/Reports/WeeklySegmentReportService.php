<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Finance\WeeklySegmentSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WeeklySegmentReportService
{
    private const EXCLUDED_CR_GL = '216220001';

    private const INCLUDED_EXCEPTION_CIFS = [
        '470000068', '470218244', '470224763', '470090458',
        '470321717', '470291487', '470317567', '470803302', '470251434',
    ];

    private const SEGMENT_MAP = [
        'CB'  => 'Corporate',
        'CM'  => 'Commercial',
        'CS'  => 'Consumer',
        'OT'  => 'Others',
        'ALL' => 'Totals',
    ];

    private const SEGMENT_ORDER = ['CB' => 1, 'CM' => 2, 'CS' => 3, 'OT' => 4];

    /**
     * Build the full weekly report dataset.
     *
     * @param string|null $weekStartOverride Explicit comparison start date (YYYY-MM-DD).
     *        When omitted, week_start is auto-derived as the latest available
     *        balance_date on/before (weekEnd − 7 days).
     * @return array{periods: array, bank: array, lcy: array, fcy: array}
     */
    public function build(string $weekEnd, ?string $weekStartOverride = null): array
    {
        $weekEnd   = Carbon::parse($weekEnd)->toDateString();
        $weekStart = $weekStartOverride !== null
            ? Carbon::parse($weekStartOverride)->toDateString()
            : $this->findWeekStart($weekEnd);
        $mtdStart  = $this->findMtdStart($weekEnd);
        $ytdStart  = $this->findYtdStart($weekEnd);

        return [
            'periods' => [
                'week_start' => $weekStart,
                'week_end'   => $weekEnd,
                'mtd_start'  => $mtdStart,
                'ytd_start'  => $ytdStart,
            ],
            'bank' => [
                'segments' => $this->buildSegmentData($weekStart, $weekEnd, $mtdStart, $ytdStart, 'BANK'),
            ],
            'lcy' => [
                'segments' => $this->buildSegmentData($weekStart, $weekEnd, $mtdStart, $ytdStart, 'LCY'),
            ],
            'fcy' => [
                'segments' => $this->buildSegmentData($weekStart, $weekEnd, $mtdStart, $ytdStart, 'FCY'),
            ],
        ];
    }

    /**
     * Persist a build() result into weekly_segment_snapshots.
     * Replaces any existing rows for the same report_date.
     *
     * @return int rows inserted
     */
    public function persist(array $data): int
    {
        $periods = $data['periods'];
        $weekEnd = $periods['week_end'];

        WeeklySegmentSnapshot::where('report_date', $weekEnd)->delete();

        $bankSegs = collect($data['bank']['segments'] ?? [])->keyBy('code');
        $lcySegs  = collect($data['lcy']['segments'] ?? [])->keyBy('code');
        $fcySegs  = collect($data['fcy']['segments'] ?? [])->keyBy('code');
        $allCodes = $bankSegs->keys()->merge($lcySegs->keys())->merge($fcySegs->keys())->unique()->values();

        $rows = [];
        $now  = now();

        foreach ($allCodes as $code) {
            $bank = $bankSegs->get($code, []);
            $lcy  = $lcySegs->get($code, []);
            $fcy  = $fcySegs->get($code, []);

            $rows[] = $this->buildRow($periods, (string) $code, '', $bank, $lcy, $fcy, $now);

            $bankSubs = collect($bank['sub_segments'] ?? [])->keyBy('name');
            $lcySubs  = collect($lcy['sub_segments']  ?? [])->keyBy('name');
            $fcySubs  = collect($fcy['sub_segments']  ?? [])->keyBy('name');
            $allSubs  = $bankSubs->keys()->merge($lcySubs->keys())->merge($fcySubs->keys())->unique()->values();

            foreach ($allSubs as $subName) {
                $rows[] = $this->buildRow(
                    $periods,
                    (string) $code,
                    (string) $subName,
                    $bankSubs->get($subName, []),
                    $lcySubs->get($subName, []),
                    $fcySubs->get($subName, []),
                    $now
                );
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            WeeklySegmentSnapshot::insert($chunk);
        }

        return count($rows);
    }

    /**
     * Load a previously persisted build() result from weekly_segment_snapshots.
     * Returns null if no data exists for weekEnd.
     *
     * @return array|null  same structure as build()
     */
    public function loadFromTable(string $weekEnd): ?array
    {
        $rows = WeeklySegmentSnapshot::where('report_date', $weekEnd)
            ->orderBy('segment_code')
            ->orderBy('sub_segment_name')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $first = $rows->first();

        return [
            'periods' => [
                'week_start' => $first->week_start->toDateString(),
                'week_end'   => $weekEnd,
                'mtd_start'  => $first->mtd_start->toDateString(),
                'ytd_start'  => $first->ytd_start->toDateString(),
            ],
            'bank' => ['segments' => $this->reconstructSegments($rows, 'bank')],
            'lcy'  => ['segments' => $this->reconstructSegments($rows, 'lcy')],
            'fcy'  => ['segments' => $this->reconstructSegments($rows, 'fcy')],
        ];
    }

    // -------------------------------------------------------------------------
    // Persist helpers
    // -------------------------------------------------------------------------

    private function buildRow(array $periods, string $code, string $subName, array $bank, array $lcy, array $fcy, \Carbon\Carbon $now): array
    {
        return [
            'report_date'         => $periods['week_end'],
            'week_start'          => $periods['week_start'],
            'mtd_start'           => $periods['mtd_start'],
            'ytd_start'           => $periods['ytd_start'],
            'segment_code'        => $code,
            'sub_segment_name'    => $subName,
            'bank_weekly_mv'      => (float) ($bank['weekly_mv']      ?? 0),
            'bank_mtd_mv'         => (float) ($bank['mtd_mv']         ?? 0),
            'bank_ytd_mv'         => (float) ($bank['ytd_mv']         ?? 0),
            'bank_total_deposits' => (float) ($bank['total_deposits'] ?? 0),
            'lcy_weekly_mv'       => (float) ($lcy['weekly_mv']       ?? 0),
            'lcy_mtd_mv'          => (float) ($lcy['mtd_mv']          ?? 0),
            'lcy_ytd_mv'          => (float) ($lcy['ytd_mv']          ?? 0),
            'lcy_total_deposits'  => (float) ($lcy['total_deposits']  ?? 0),
            'fcy_weekly_mv'       => (float) ($fcy['weekly_mv']       ?? 0),
            'fcy_mtd_mv'          => (float) ($fcy['mtd_mv']          ?? 0),
            'fcy_ytd_mv'          => (float) ($fcy['ytd_mv']          ?? 0),
            'fcy_total_deposits'  => (float) ($fcy['total_deposits']  ?? 0),
            'created_at'          => $now,
            'updated_at'          => $now,
        ];
    }

    private function reconstructSegments(Collection $rows, string $currency): array
    {
        $order       = ['CB' => 1, 'CM' => 2, 'CS' => 3, 'OT' => 4, 'ALL' => 99];
        $segRows     = $rows->where('sub_segment_name', '');
        $subSegRows  = $rows->where('sub_segment_name', '!=', '');
        $subByCode   = $subSegRows->groupBy('segment_code');

        $segments = [];

        foreach ($segRows as $row) {
            $code = $row->segment_code;

            $subSegments = [];
            foreach ($subByCode->get($code, collect()) as $sub) {
                $subSegments[] = [
                    'name'           => $sub->sub_segment_name,
                    'weekly_mv'      => (float) $sub->{$currency . '_weekly_mv'},
                    'mtd_mv'         => (float) $sub->{$currency . '_mtd_mv'},
                    'ytd_mv'         => (float) $sub->{$currency . '_ytd_mv'},
                    'total_deposits' => (float) $sub->{$currency . '_total_deposits'},
                ];
            }

            usort($subSegments, fn($a, $b) => $a['name'] <=> $b['name']);

            $segments[$code] = [
                'code'           => $code,
                'name'           => self::SEGMENT_MAP[$code] ?? $code,
                'weekly_mv'      => (float) $row->{$currency . '_weekly_mv'},
                'mtd_mv'         => (float) $row->{$currency . '_mtd_mv'},
                'ytd_mv'         => (float) $row->{$currency . '_ytd_mv'},
                'total_deposits' => (float) $row->{$currency . '_total_deposits'},
                'sub_segments'   => $subSegments,
            ];
        }

        uasort($segments, fn($a, $b) => ($order[$a['code']] ?? 50) <=> ($order[$b['code']] ?? 50));

        return array_values($segments);
    }

    /**
     * Overall (cross sub-segment, whole bank — any currency) top movers for a
     * given period. Used by the CIF Drilldown sheet (week only, top 100).
     *
     * @return array{gainers: Collection, losers: Collection}
     */
    public function topMovers(string $start, string $end, int $limit = 10): array
    {
        $rows = collect($this->fetchCifMovementRows($start, $end));

        $gainers = $rows->filter(fn($r) => (float) $r->movement > 0)
            ->sortByDesc(fn($r) => (float) $r->movement)
            ->take($limit)
            ->values();

        $losers = $rows->filter(fn($r) => (float) $r->movement < 0)
            ->sortBy(fn($r) => (float) $r->movement)
            ->take($limit)
            ->values();

        return ['gainers' => $gainers, 'losers' => $losers];
    }

    /**
     * Per-CIF movement between two balance dates, whole bank (any currency),
     * joined to its sub-segment name. Shared by drilldown() (grouped by
     * sub-segment) and topMovers() (overall ranking).
     */
    private function fetchCifMovementRows(string $start, string $end): array
    {
        $exceptionPh = implode(',', array_fill(0, count(self::INCLUDED_EXCEPTION_CIFS), '?'));

        $rows = DB::select("
            SELECT
                m.cif,
                m.customer_name,
                m.branch_code,
                COALESCE(css.segment_code, 'OT') AS business_segment_code,
                COALESCE(css.sub_segment_name, 'Unmapped') AS sub_segment_name,
                m.period_start  AS start_balance,
                m.period_end    AS end_balance,
                (m.period_end - m.period_start) AS movement
            FROM (
                SELECT
                    cb.cif,
                    MAX(cb.customer_name)               AS customer_name,
                    MAX(UPPER(TRIM(cb.branch_code)))    AS branch_code,
                    SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS period_start,
                    SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS period_end
                FROM customer_balances cb
                WHERE cb.balance_date IN (?, ?)
                  AND cb.cif IS NOT NULL
                  AND (
                        cb.cif IN ({$exceptionPh})
                        OR (
                            UPPER(TRIM(cb.branch_code)) <> 'P50'
                            AND (cb.cr_gl IS NULL OR cb.cr_gl <> ?)
                        )
                  )
                GROUP BY cb.cif
            ) m
            LEFT JOIN (
                SELECT
                    x.cif,
                    CASE
                        WHEN SUM(CASE WHEN x.seg = 'CB' THEN 1 ELSE 0 END) > 0 THEN 'CB'
                        WHEN SUM(CASE WHEN x.seg = 'CM' THEN 1 ELSE 0 END) > 0 THEN 'CM'
                        WHEN SUM(CASE WHEN x.seg = 'CS' THEN 1 ELSE 0 END) > 0 THEN 'CS'
                        ELSE 'OT'
                    END AS segment_code,
                    COALESCE(
                        CASE
                            WHEN SUM(CASE WHEN x.seg = 'CB' THEN 1 ELSE 0 END) > 0
                                THEN MIN(CASE WHEN x.seg = 'CB' THEN x.sub_segment_name END)
                            WHEN SUM(CASE WHEN x.seg = 'CM' THEN 1 ELSE 0 END) > 0
                                THEN MIN(CASE WHEN x.seg = 'CM' THEN x.sub_segment_name END)
                            WHEN SUM(CASE WHEN x.seg = 'CS' THEN 1 ELSE 0 END) > 0
                                THEN MIN(CASE WHEN x.seg = 'CS' THEN x.sub_segment_name END)
                            ELSE 'Unmapped'
                        END,
                        'Unmapped'
                    ) AS sub_segment_name
                FROM (
                    SELECT
                        cai.f12_cif AS cif,
                        CASE
                            /* Use the mapping table first. Some CS-prefixed MIS codes, e.g. CSMS_2100, are Commercial. */
                            WHEN UPPER(TRIM(COALESCE(sm.business, ''))) = 'CORPORATE BANKING' THEN 'CB'
                            WHEN UPPER(TRIM(COALESCE(sm.business, ''))) = 'COMMERCIAL BANKING' THEN 'CM'
                            WHEN UPPER(TRIM(COALESCE(sm.business, ''))) = 'CONSUMER BANKING' THEN 'CS'

                            /* Fallback only when the MIS code is not mapped. */
                            WHEN UPPER(TRIM(cai.etibiseg2)) LIKE 'CB%' THEN 'CB'
                            WHEN UPPER(TRIM(cai.etibiseg2)) LIKE 'CM%' THEN 'CM'
                            WHEN UPPER(TRIM(cai.etibiseg2)) LIKE 'CS%' THEN 'CS'
                            ELSE 'OT'
                        END AS seg,
                        COALESCE(NULLIF(TRIM(sm.business_segment_name), ''), 'Unmapped') AS sub_segment_name
                    FROM customer_accounts_imports cai
                    LEFT JOIN sub_segment_mappings sm
                        ON UPPER(TRIM(sm.mis_code)) = UPPER(TRIM(cai.etibiseg2))
                       AND sm.is_active = 1
                    WHERE cai.f12_cif IS NOT NULL
                      AND cai.etibiseg2 IS NOT NULL
                      AND TRIM(cai.etibiseg2) <> ''
                ) x
                GROUP BY x.cif
            ) css ON css.cif = m.cif
            HAVING (m.period_end - m.period_start) <> 0
        ", array_merge(
            [$start, $end, $start, $end],
            self::INCLUDED_EXCEPTION_CIFS,
            [self::EXCLUDED_CR_GL]
        ));

        foreach ($rows as $row) {
            $row->business_segment_name = self::SEGMENT_MAP[$row->business_segment_code] ?? $row->business_segment_code;
        }

        return $rows;
    }

    // -------------------------------------------------------------------------
    // Private builders
    // -------------------------------------------------------------------------

    private function buildSegmentData(
        string $weekStart,
        string $weekEnd,
        string $mtdStart,
        string $ytdStart,
        string $currencyType
    ): array {
        $segRows    = $this->querySegmentTotals($weekStart, $weekEnd, $mtdStart, $ytdStart, $currencyType);
        $subSegRows = $this->querySubSegmentTotals($weekStart, $weekEnd, $mtdStart, $ytdStart, $currencyType);

        $subBySegment = collect($subSegRows)->groupBy('segment_code');

        $segments = [];
        $totals   = ['ws' => 0.0, 'we' => 0.0, 'ms' => 0.0, 'ys' => 0.0];

        foreach ($segRows as $row) {
            $code = strtoupper((string) ($row->segment_code ?? 'OT'));
            if (!isset(self::SEGMENT_MAP[$code]) || $code === 'ALL') {
                $code = 'OT';
            }

            $ws = (float) ($row->weekly_start ?? 0);
            $we = (float) ($row->weekly_end   ?? 0);
            $ms = (float) ($row->mtd_start    ?? 0);
            $ys = (float) ($row->ytd_start    ?? 0);

            $totals['ws'] += $ws;
            $totals['we'] += $we;
            $totals['ms'] += $ms;
            $totals['ys'] += $ys;

            $subSegments = [];
            foreach ($subBySegment->get($code, collect()) as $sub) {
                $subName = (string) ($sub->sub_segment_name ?? 'Unmapped');

                // Skip Unmapped in all segments; OT has no meaningful sub-segment breakdown.
                if ($subName === 'Unmapped' || $code === 'OT') {
                    continue;
                }

                $sws = (float) ($sub->weekly_start ?? 0);
                $swe = (float) ($sub->weekly_end   ?? 0);
                $sms = (float) ($sub->mtd_start    ?? 0);
                $sys = (float) ($sub->ytd_start    ?? 0);

                $subSegments[] = [
                    'name'           => $subName,
                    'weekly_mv'      => $swe - $sws,
                    'mtd_mv'         => $swe - $sms,
                    'ytd_mv'         => $swe - $sys,
                    'total_deposits' => $swe,
                ];
            }

            usort($subSegments, fn($a, $b) => $a['name'] <=> $b['name']);

            $segments[$code] = [
                'code'           => $code,
                'name'           => self::SEGMENT_MAP[$code],
                'weekly_mv'      => $we - $ws,
                'mtd_mv'         => $we - $ms,
                'ytd_mv'         => $we - $ys,
                'total_deposits' => $we,
                'sub_segments'   => $subSegments,
            ];
        }

        uasort($segments, fn($a, $b) =>
            (self::SEGMENT_ORDER[$a['code']] ?? 50) <=> (self::SEGMENT_ORDER[$b['code']] ?? 50)
        );

        // Append TOTAL row
        $segments['ALL'] = [
            'code'           => 'ALL',
            'name'           => 'Totals',
            'weekly_mv'      => $totals['we'] - $totals['ws'],
            'mtd_mv'         => $totals['we'] - $totals['ms'],
            'ytd_mv'         => $totals['we'] - $totals['ys'],
            'total_deposits' => $totals['we'],
            'sub_segments'   => [],
        ];

        return array_values($segments);
    }

    // -------------------------------------------------------------------------
    // Raw SQL queries (same proven pattern as SegmentMoversService)
    // -------------------------------------------------------------------------

    /**
     * LCY  = currency is KES
     * FCY  = currency is anything other than KES
     * BANK = whole bank, no currency restriction
     */
    private function currencyFilterSql(string $currencyType): string
    {
        return match (strtoupper($currencyType)) {
            'LCY'   => "(UPPER(TRIM(COALESCE(cb.currency, ''))) = 'KES')",
            'FCY'   => "(UPPER(TRIM(COALESCE(cb.currency, ''))) <> 'KES')",
            default => '1=1',
        };
    }

    private function querySegmentTotals(
        string $weekStart,
        string $weekEnd,
        string $mtdStart,
        string $ytdStart,
        string $currencyType
    ): array {
        $dates       = array_values(array_unique([$weekStart, $weekEnd, $mtdStart, $ytdStart]));
        $datePh      = implode(',', array_fill(0, count($dates), '?'));
        $exceptionPh = implode(',', array_fill(0, count(self::INCLUDED_EXCEPTION_CIFS), '?'));

        $currencyFilter = $this->currencyFilterSql($currencyType);

        return DB::select("
            SELECT
                COALESCE(s.segment_code, 'OT') AS segment_code,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS weekly_start,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS weekly_end,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS mtd_start,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS ytd_start
            FROM customer_balances cb

            LEFT JOIN (
                SELECT x.cif,
                    CASE
                        WHEN SUM(CASE WHEN x.seg = 'CB' THEN 1 ELSE 0 END) > 0 THEN 'CB'
                        WHEN SUM(CASE WHEN x.seg = 'CM' THEN 1 ELSE 0 END) > 0 THEN 'CM'
                        WHEN SUM(CASE WHEN x.seg = 'CS' THEN 1 ELSE 0 END) > 0 THEN 'CS'
                        ELSE NULL
                    END AS segment_code
                FROM (
                    SELECT
                        cai.f12_cif AS cif,
                        CASE
                            /* Use the mapping table first. Some CS-prefixed MIS codes, e.g. CSMS_2100, are Commercial. */
                            WHEN UPPER(TRIM(COALESCE(sm.business, ''))) = 'CORPORATE BANKING' THEN 'CB'
                            WHEN UPPER(TRIM(COALESCE(sm.business, ''))) = 'COMMERCIAL BANKING' THEN 'CM'
                            WHEN UPPER(TRIM(COALESCE(sm.business, ''))) = 'CONSUMER BANKING' THEN 'CS'

                            /* Fallback only when the MIS code is not mapped. */
                            WHEN UPPER(TRIM(cai.etibiseg2)) LIKE 'CB%' THEN 'CB'
                            WHEN UPPER(TRIM(cai.etibiseg2)) LIKE 'CM%' THEN 'CM'
                            WHEN UPPER(TRIM(cai.etibiseg2)) LIKE 'CS%' THEN 'CS'
                            ELSE NULL
                        END AS seg
                    FROM customer_accounts_imports cai
                    LEFT JOIN sub_segment_mappings sm
                        ON UPPER(TRIM(sm.mis_code)) = UPPER(TRIM(cai.etibiseg2))
                       AND sm.is_active = 1
                    WHERE cai.f12_cif IS NOT NULL
                      AND cai.etibiseg2 IS NOT NULL
                      AND TRIM(cai.etibiseg2) <> ''
                ) x
                WHERE x.seg IS NOT NULL
                GROUP BY x.cif
            ) s ON s.cif = cb.cif

            WHERE cb.balance_date IN ({$datePh})
              AND cb.cif IS NOT NULL
              AND {$currencyFilter}
              AND (
                    cb.cif IN ({$exceptionPh})
                    OR (
                        UPPER(TRIM(cb.branch_code)) <> 'P50'
                        AND (cb.cr_gl IS NULL OR cb.cr_gl <> ?)
                    )
              )
            GROUP BY COALESCE(s.segment_code, 'OT')
        ", array_merge(
            [$weekStart, $weekEnd, $mtdStart, $ytdStart],
            $dates,
            self::INCLUDED_EXCEPTION_CIFS,
            [self::EXCLUDED_CR_GL]
        ));
    }

    private function querySubSegmentTotals(
        string $weekStart,
        string $weekEnd,
        string $mtdStart,
        string $ytdStart,
        string $currencyType
    ): array {
        $dates       = array_values(array_unique([$weekStart, $weekEnd, $mtdStart, $ytdStart]));
        $datePh      = implode(',', array_fill(0, count($dates), '?'));
        $exceptionPh = implode(',', array_fill(0, count(self::INCLUDED_EXCEPTION_CIFS), '?'));

        $currencyFilter = $this->currencyFilterSql($currencyType);

        return DB::select("
            SELECT
                COALESCE(cai.segment_code, 'OT') AS segment_code,
                COALESCE(cai.sub_segment_name, 'Unmapped') AS sub_segment_name,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS weekly_start,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS weekly_end,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS mtd_start,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS ytd_start
            FROM customer_balances cb
            LEFT JOIN (
                SELECT
                    x.cif,
                    CASE
                        WHEN SUM(CASE WHEN x.segment_code = 'CB' THEN 1 ELSE 0 END) > 0 THEN 'CB'
                        WHEN SUM(CASE WHEN x.segment_code = 'CM' THEN 1 ELSE 0 END) > 0 THEN 'CM'
                        WHEN SUM(CASE WHEN x.segment_code = 'CS' THEN 1 ELSE 0 END) > 0 THEN 'CS'
                        ELSE 'OT'
                    END AS segment_code,
                    COALESCE(
                        CASE
                            WHEN SUM(CASE WHEN x.segment_code = 'CB' THEN 1 ELSE 0 END) > 0
                                THEN MIN(CASE WHEN x.segment_code = 'CB' THEN x.sub_segment_name END)
                            WHEN SUM(CASE WHEN x.segment_code = 'CM' THEN 1 ELSE 0 END) > 0
                                THEN MIN(CASE WHEN x.segment_code = 'CM' THEN x.sub_segment_name END)
                            WHEN SUM(CASE WHEN x.segment_code = 'CS' THEN 1 ELSE 0 END) > 0
                                THEN MIN(CASE WHEN x.segment_code = 'CS' THEN x.sub_segment_name END)
                            ELSE 'Unmapped'
                        END,
                        'Unmapped'
                    ) AS sub_segment_name
                FROM (
                    SELECT
                        cai.f12_cif AS cif,
                        CASE
                            /* Use the mapping table first. Some CS-prefixed MIS codes, e.g. CSMS_2100, are Commercial. */
                            WHEN UPPER(TRIM(COALESCE(sm.business, ''))) = 'CORPORATE BANKING' THEN 'CB'
                            WHEN UPPER(TRIM(COALESCE(sm.business, ''))) = 'COMMERCIAL BANKING' THEN 'CM'
                            WHEN UPPER(TRIM(COALESCE(sm.business, ''))) = 'CONSUMER BANKING' THEN 'CS'

                            /* Fallback only when the MIS code is not mapped. */
                            WHEN UPPER(TRIM(cai.etibiseg2)) LIKE 'CB%' THEN 'CB'
                            WHEN UPPER(TRIM(cai.etibiseg2)) LIKE 'CM%' THEN 'CM'
                            WHEN UPPER(TRIM(cai.etibiseg2)) LIKE 'CS%' THEN 'CS'
                            ELSE 'OT'
                        END AS segment_code,
                        CASE
                            WHEN UPPER(TRIM(COALESCE(sm.business, ''))) IN ('CORPORATE BANKING', 'COMMERCIAL BANKING', 'CONSUMER BANKING')
                              OR UPPER(TRIM(cai.etibiseg2)) LIKE 'CB%'
                              OR UPPER(TRIM(cai.etibiseg2)) LIKE 'CM%'
                              OR UPPER(TRIM(cai.etibiseg2)) LIKE 'CS%'
                            THEN COALESCE(NULLIF(TRIM(sm.business_segment_name), ''), 'Unmapped')
                            ELSE 'Unmapped'
                        END AS sub_segment_name
                    FROM customer_accounts_imports cai
                    LEFT JOIN sub_segment_mappings sm
                        ON UPPER(TRIM(sm.mis_code)) = UPPER(TRIM(cai.etibiseg2))
                       AND sm.is_active = 1
                    WHERE cai.f12_cif IS NOT NULL
                      AND cai.etibiseg2 IS NOT NULL
                      AND TRIM(cai.etibiseg2) <> ''
                ) x
                GROUP BY x.cif
            ) cai ON cai.cif = cb.cif
            WHERE cb.balance_date IN ({$datePh})
              AND cb.cif IS NOT NULL
              AND {$currencyFilter}
              AND (
                    cb.cif IN ({$exceptionPh})
                    OR (
                        UPPER(TRIM(cb.branch_code)) <> 'P50'
                        AND (cb.cr_gl IS NULL OR cb.cr_gl <> ?)
                    )
              )
            GROUP BY
                COALESCE(cai.segment_code, 'OT'),
                COALESCE(cai.sub_segment_name, 'Unmapped')
        ", array_merge(
            [$weekStart, $weekEnd, $mtdStart, $ytdStart],
            $dates,
            self::INCLUDED_EXCEPTION_CIFS,
            [self::EXCLUDED_CR_GL]
        ));
    }

    // -------------------------------------------------------------------------
    // Date helpers
    // -------------------------------------------------------------------------

    /** Most recent balance_date available in customer_balances. */
    public function findLatestBalanceDate(): string
    {
        $d = DB::table('customer_balances')->max('balance_date');
        return $d ? Carbon::parse((string) $d)->toDateString() : now()->timezone('Africa/Nairobi')->toDateString();
    }

    /** Whether any customer_balances rows exist for a given balance_date. */
    public function hasBalanceDataOn(string $date): bool
    {
        return DB::table('customer_balances')
            ->where('balance_date', Carbon::parse($date)->toDateString())
            ->exists();
    }

    /** Latest balance_date on or before (weekEnd − 7 days). */
    private function findWeekStart(string $weekEnd): string
    {
        $target = Carbon::parse($weekEnd)->subDays(7)->toDateString();

        $d = DB::table('customer_balances')
            ->where('balance_date', '<=', $target)
            ->max('balance_date');

        return $d ? Carbon::parse((string) $d)->toDateString() : $target;
    }

    /** Latest balance_date in the previous calendar month. */
    private function findMtdStart(string $weekEnd): string
    {
        $prevMonthEnd = Carbon::parse($weekEnd)->startOfMonth()->subDay();

        // Last available date in the previous month
        $d = DB::table('customer_balances')
            ->whereYear('balance_date', $prevMonthEnd->year)
            ->whereMonth('balance_date', $prevMonthEnd->month)
            ->max('balance_date');

        if ($d) return Carbon::parse((string) $d)->toDateString();

        // Fallback: any date before the start of the current month
        $d2 = DB::table('customer_balances')
            ->where('balance_date', '<', Carbon::parse($weekEnd)->startOfMonth()->toDateString())
            ->max('balance_date');

        return $d2 ? Carbon::parse((string) $d2)->toDateString() : $weekEnd;
    }

    /** Latest balance_date of the previous calendar year (e.g. 2025-12-31). */
    private function findYtdStart(string $date): string
    {
        $yearStart = Carbon::parse($date)->startOfYear()->toDateString();

        $d = DB::table('customer_balances')
            ->where('balance_date', '<', $yearStart)
            ->max('balance_date');

        if ($d) return Carbon::parse((string) $d)->toDateString();

        // No prior-year data: earliest available balance in current year
        $d2 = DB::table('customer_balances')
            ->where('balance_date', '>=', $yearStart)
            ->where('balance_date', '<', $date)
            ->min('balance_date');

        return $d2 ? Carbon::parse((string) $d2)->toDateString() : $date;
    }

    // -------------------------------------------------------------------------
    // Historical comparison section
    // -------------------------------------------------------------------------

    /**
     * Build the historical comparison dataset (Bank + LCY + FCY) for the email/Excel section.
     *
     * Columns: YE | Month-3 | Month-2 | Month-1 | W-1 movement
     *
     * @return array{labels: array, bank: array, lcy: array, fcy: array}
     */
    public function buildHistoricalSection(string $weekEnd): array
    {
        $weekEnd = Carbon::parse($weekEnd)->toDateString();
        $we      = Carbon::parse($weekEnd);

        $yeDate = $this->findLatestDateInMonth($we->year - 1, 12);
        $m3Date = $this->findLatestDateInMonth($we->copy()->subMonths(3)->year, $we->copy()->subMonths(3)->month);
        $m2Date = $this->findLatestDateInMonth($we->copy()->subMonths(2)->year, $we->copy()->subMonths(2)->month);
        $m1Date = $this->findLatestDateInMonth($we->copy()->subMonths(1)->year, $we->copy()->subMonths(1)->month);
        $w1Start = $this->findWeekStart($weekEnd);

        $fmtYe  = fn(?string $d) => $d ? Carbon::parse($d)->format('d M Y') : '—';
        $fmtMon = fn(?string $d) => $d ? Carbon::parse($d)->format('M Y')   : '—';
        $fmtDay = fn(?string $d) => $d ? Carbon::parse($d)->format('d M')   : '—';

        $labels = [
            'ye' => 'YE ' . ($we->year - 1) . ' (' . $fmtYe($yeDate) . ')',
            'm3' => $fmtMon($m3Date),
            'm2' => $fmtMon($m2Date),
            'm1' => $fmtMon($m1Date),
            'w1' => 'W-1 (' . $fmtDay($w1Start) . ' → ' . $fmtDay($weekEnd) . ')',
        ];

        return [
            'labels' => $labels,
            'bank'   => ['segments' => $this->buildHistoricalSegments($yeDate, $m3Date, $m2Date, $m1Date, $w1Start, $weekEnd, 'BANK')],
            'lcy'    => ['segments' => $this->buildHistoricalSegments($yeDate, $m3Date, $m2Date, $m1Date, $w1Start, $weekEnd, 'LCY')],
            'fcy'    => ['segments' => $this->buildHistoricalSegments($yeDate, $m3Date, $m2Date, $m1Date, $w1Start, $weekEnd, 'FCY')],
        ];
    }

    private function buildHistoricalSegments(
        ?string $yeDate,
        ?string $m3Date,
        ?string $m2Date,
        ?string $m1Date,
        string  $w1Start,
        string  $weekEnd,
        string  $currencyType
    ): array {
        $segRows    = $this->queryHistoricalSegmentTotals($yeDate, $m3Date, $m2Date, $m1Date, $w1Start, $weekEnd, $currencyType);
        $subSegRows = $this->queryHistoricalSubSegmentTotals($yeDate, $m3Date, $m2Date, $m1Date, $w1Start, $weekEnd, $currencyType);

        $subBySegment = collect($subSegRows)->groupBy('segment_code');

        $segments = [];
        $totals   = ['ye' => 0.0, 'm3' => 0.0, 'm2' => 0.0, 'm1' => 0.0, 'w1s' => 0.0, 'w1e' => 0.0];

        foreach ($segRows as $row) {
            $code = strtoupper((string) ($row->segment_code ?? 'OT'));
            if (!isset(self::SEGMENT_MAP[$code]) || $code === 'ALL') {
                $code = 'OT';
            }

            $ye  = (float) ($row->ye_balance   ?? 0);
            $m3  = (float) ($row->m3_balance   ?? 0);
            $m2  = (float) ($row->m2_balance   ?? 0);
            $m1  = (float) ($row->m1_balance   ?? 0);
            $w1s = (float) ($row->w1_start_bal ?? 0);
            $w1e = (float) ($row->w1_end_bal   ?? 0);

            $totals['ye']  += $ye;
            $totals['m3']  += $m3;
            $totals['m2']  += $m2;
            $totals['m1']  += $m1;
            $totals['w1s'] += $w1s;
            $totals['w1e'] += $w1e;

            $subSegments = [];
            if ($code !== 'OT') {
                foreach ($subBySegment->get($code, collect()) as $sub) {
                    $subName = (string) ($sub->sub_segment_name ?? 'Unmapped');
                    if ($subName === 'Unmapped') continue;
                    $subSegments[] = [
                        'name'   => $subName,
                        'ye_bal' => (float) ($sub->ye_balance   ?? 0),
                        'm3_bal' => (float) ($sub->m3_balance   ?? 0),
                        'm2_bal' => (float) ($sub->m2_balance   ?? 0),
                        'm1_bal' => (float) ($sub->m1_balance   ?? 0),
                        'w1_mv'  => (float) ($sub->w1_end_bal   ?? 0) - (float) ($sub->w1_start_bal ?? 0),
                    ];
                }
                usort($subSegments, fn($a, $b) => $a['name'] <=> $b['name']);
            }

            $segments[$code] = [
                'code'         => $code,
                'name'         => self::SEGMENT_MAP[$code],
                'ye_bal'       => $ye,
                'm3_bal'       => $m3,
                'm2_bal'       => $m2,
                'm1_bal'       => $m1,
                'w1_mv'        => $w1e - $w1s,
                'sub_segments' => $subSegments,
            ];
        }

        uasort($segments, fn($a, $b) =>
            (self::SEGMENT_ORDER[$a['code']] ?? 50) <=> (self::SEGMENT_ORDER[$b['code']] ?? 50)
        );

        $segments['ALL'] = [
            'code'         => 'ALL',
            'name'         => 'Totals',
            'ye_bal'       => $totals['ye'],
            'm3_bal'       => $totals['m3'],
            'm2_bal'       => $totals['m2'],
            'm1_bal'       => $totals['m1'],
            'w1_mv'        => $totals['w1e'] - $totals['w1s'],
            'sub_segments' => [],
        ];

        return array_values($segments);
    }

    /** Latest balance_date within a specific year+month, or null if none. */
    private function findLatestDateInMonth(int $year, int $month): ?string
    {
        $d = DB::table('customer_balances')
            ->whereYear('balance_date', $year)
            ->whereMonth('balance_date', $month)
            ->max('balance_date');

        return $d ? Carbon::parse((string) $d)->toDateString() : null;
    }

    private function queryHistoricalSegmentTotals(
        ?string $yeDate,
        ?string $m3Date,
        ?string $m2Date,
        ?string $m1Date,
        string  $w1Start,
        string  $w1End,
        string  $currencyType
    ): array {
        $sentinel    = '1900-01-01';
        $ye          = $yeDate ?? $sentinel;
        $m3          = $m3Date ?? $sentinel;
        $m2          = $m2Date ?? $sentinel;
        $m1          = $m1Date ?? $sentinel;

        $currencyFilter = $this->currencyFilterSql($currencyType);

        $dates       = array_values(array_unique([$ye, $m3, $m2, $m1, $w1Start, $w1End]));
        $datePh      = implode(',', array_fill(0, count($dates), '?'));
        $exceptionPh = implode(',', array_fill(0, count(self::INCLUDED_EXCEPTION_CIFS), '?'));

        return DB::select("
            SELECT
                COALESCE(s.segment_code, 'OT') AS segment_code,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS ye_balance,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS m3_balance,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS m2_balance,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS m1_balance,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS w1_start_bal,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS w1_end_bal
            FROM customer_balances cb

            LEFT JOIN (
                SELECT x.cif,
                    CASE
                        WHEN SUM(CASE WHEN x.seg = 'CB' THEN 1 ELSE 0 END) > 0 THEN 'CB'
                        WHEN SUM(CASE WHEN x.seg = 'CM' THEN 1 ELSE 0 END) > 0 THEN 'CM'
                        WHEN SUM(CASE WHEN x.seg = 'CS' THEN 1 ELSE 0 END) > 0 THEN 'CS'
                        ELSE NULL
                    END AS segment_code
                FROM (
                    SELECT
                        cai.f12_cif AS cif,
                        CASE
                            /* Use the mapping table first. Some CS-prefixed MIS codes, e.g. CSMS_2100, are Commercial. */
                            WHEN UPPER(TRIM(COALESCE(sm.business, ''))) = 'CORPORATE BANKING' THEN 'CB'
                            WHEN UPPER(TRIM(COALESCE(sm.business, ''))) = 'COMMERCIAL BANKING' THEN 'CM'
                            WHEN UPPER(TRIM(COALESCE(sm.business, ''))) = 'CONSUMER BANKING' THEN 'CS'

                            /* Fallback only when the MIS code is not mapped. */
                            WHEN UPPER(TRIM(cai.etibiseg2)) LIKE 'CB%' THEN 'CB'
                            WHEN UPPER(TRIM(cai.etibiseg2)) LIKE 'CM%' THEN 'CM'
                            WHEN UPPER(TRIM(cai.etibiseg2)) LIKE 'CS%' THEN 'CS'
                            ELSE NULL
                        END AS seg
                    FROM customer_accounts_imports cai
                    LEFT JOIN sub_segment_mappings sm
                        ON UPPER(TRIM(sm.mis_code)) = UPPER(TRIM(cai.etibiseg2))
                       AND sm.is_active = 1
                    WHERE cai.f12_cif IS NOT NULL
                      AND cai.etibiseg2 IS NOT NULL
                      AND TRIM(cai.etibiseg2) <> ''
                ) x
                WHERE x.seg IS NOT NULL
                GROUP BY x.cif
            ) s ON s.cif = cb.cif

            WHERE cb.balance_date IN ({$datePh})
              AND cb.cif IS NOT NULL
              AND {$currencyFilter}
              AND (
                    cb.cif IN ({$exceptionPh})
                    OR (
                        UPPER(TRIM(cb.branch_code)) <> 'P50'
                        AND (cb.cr_gl IS NULL OR cb.cr_gl <> ?)
                    )
              )
            GROUP BY COALESCE(s.segment_code, 'OT')
        ", array_merge(
            [$ye, $m3, $m2, $m1, $w1Start, $w1End],
            $dates,
            self::INCLUDED_EXCEPTION_CIFS,
            [self::EXCLUDED_CR_GL]
        ));
    }

    private function queryHistoricalSubSegmentTotals(
        ?string $yeDate,
        ?string $m3Date,
        ?string $m2Date,
        ?string $m1Date,
        string  $w1Start,
        string  $w1End,
        string  $currencyType
    ): array {
        $sentinel    = '1900-01-01';
        $ye          = $yeDate ?? $sentinel;
        $m3          = $m3Date ?? $sentinel;
        $m2          = $m2Date ?? $sentinel;
        $m1          = $m1Date ?? $sentinel;

        $currencyFilter = $this->currencyFilterSql($currencyType);

        $dates       = array_values(array_unique([$ye, $m3, $m2, $m1, $w1Start, $w1End]));
        $datePh      = implode(',', array_fill(0, count($dates), '?'));
        $exceptionPh = implode(',', array_fill(0, count(self::INCLUDED_EXCEPTION_CIFS), '?'));

        return DB::select("
            SELECT
                COALESCE(cai.segment_code, 'OT') AS segment_code,
                COALESCE(cai.sub_segment_name, 'Unmapped') AS sub_segment_name,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS ye_balance,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS m3_balance,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS m2_balance,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS m1_balance,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS w1_start_bal,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS w1_end_bal
            FROM customer_balances cb
            LEFT JOIN (
                SELECT
                    x.cif,
                    CASE
                        WHEN SUM(CASE WHEN x.segment_code = 'CB' THEN 1 ELSE 0 END) > 0 THEN 'CB'
                        WHEN SUM(CASE WHEN x.segment_code = 'CM' THEN 1 ELSE 0 END) > 0 THEN 'CM'
                        WHEN SUM(CASE WHEN x.segment_code = 'CS' THEN 1 ELSE 0 END) > 0 THEN 'CS'
                        ELSE 'OT'
                    END AS segment_code,
                    COALESCE(
                        CASE
                            WHEN SUM(CASE WHEN x.segment_code = 'CB' THEN 1 ELSE 0 END) > 0
                                THEN MIN(CASE WHEN x.segment_code = 'CB' THEN x.sub_segment_name END)
                            WHEN SUM(CASE WHEN x.segment_code = 'CM' THEN 1 ELSE 0 END) > 0
                                THEN MIN(CASE WHEN x.segment_code = 'CM' THEN x.sub_segment_name END)
                            WHEN SUM(CASE WHEN x.segment_code = 'CS' THEN 1 ELSE 0 END) > 0
                                THEN MIN(CASE WHEN x.segment_code = 'CS' THEN x.sub_segment_name END)
                            ELSE 'Unmapped'
                        END,
                        'Unmapped'
                    ) AS sub_segment_name
                FROM (
                    SELECT
                        cai.f12_cif AS cif,
                        CASE
                            /* Use the mapping table first. Some CS-prefixed MIS codes, e.g. CSMS_2100, are Commercial. */
                            WHEN UPPER(TRIM(COALESCE(sm.business, ''))) = 'CORPORATE BANKING' THEN 'CB'
                            WHEN UPPER(TRIM(COALESCE(sm.business, ''))) = 'COMMERCIAL BANKING' THEN 'CM'
                            WHEN UPPER(TRIM(COALESCE(sm.business, ''))) = 'CONSUMER BANKING' THEN 'CS'

                            /* Fallback only when the MIS code is not mapped. */
                            WHEN UPPER(TRIM(cai.etibiseg2)) LIKE 'CB%' THEN 'CB'
                            WHEN UPPER(TRIM(cai.etibiseg2)) LIKE 'CM%' THEN 'CM'
                            WHEN UPPER(TRIM(cai.etibiseg2)) LIKE 'CS%' THEN 'CS'
                            ELSE 'OT'
                        END AS segment_code,
                        CASE
                            WHEN UPPER(TRIM(COALESCE(sm.business, ''))) IN ('CORPORATE BANKING', 'COMMERCIAL BANKING', 'CONSUMER BANKING')
                              OR UPPER(TRIM(cai.etibiseg2)) LIKE 'CB%'
                              OR UPPER(TRIM(cai.etibiseg2)) LIKE 'CM%'
                              OR UPPER(TRIM(cai.etibiseg2)) LIKE 'CS%'
                            THEN COALESCE(NULLIF(TRIM(sm.business_segment_name), ''), 'Unmapped')
                            ELSE 'Unmapped'
                        END AS sub_segment_name
                    FROM customer_accounts_imports cai
                    LEFT JOIN sub_segment_mappings sm
                        ON UPPER(TRIM(sm.mis_code)) = UPPER(TRIM(cai.etibiseg2))
                       AND sm.is_active = 1
                    WHERE cai.f12_cif IS NOT NULL
                      AND cai.etibiseg2 IS NOT NULL
                      AND TRIM(cai.etibiseg2) <> ''
                ) x
                GROUP BY x.cif
            ) cai ON cai.cif = cb.cif
            WHERE cb.balance_date IN ({$datePh})
              AND cb.cif IS NOT NULL
              AND {$currencyFilter}
              AND (
                    cb.cif IN ({$exceptionPh})
                    OR (
                        UPPER(TRIM(cb.branch_code)) <> 'P50'
                        AND (cb.cr_gl IS NULL OR cb.cr_gl <> ?)
                    )
              )
            GROUP BY
                COALESCE(cai.segment_code, 'OT'),
                COALESCE(cai.sub_segment_name, 'Unmapped')
        ", array_merge(
            [$ye, $m3, $m2, $m1, $w1Start, $w1End],
            $dates,
            self::INCLUDED_EXCEPTION_CIFS,
            [self::EXCLUDED_CR_GL]
        ));
    }
}
