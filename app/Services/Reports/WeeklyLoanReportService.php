<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Finance\WeeklyLoanSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WeeklyLoanReportService
{
    private const SEGMENT_MAP = [
        'CORPORATE BANKING'  => 'Corporate',
        'COMMERCIAL BANKING' => 'Commercial',
        'CONSUMER BANKING'   => 'Consumer',
        'UNMAPPED'           => 'Unmapped',
        'ALL'                => 'Totals',
    ];

    private const SEGMENT_ORDER = [
        'CORPORATE BANKING'  => 1,
        'COMMERCIAL BANKING' => 2,
        'CONSUMER BANKING'   => 3,
        'UNMAPPED'           => 4,
    ];

    // Only loans in these statuses (or blank) count as the active/performing book —
    // same filter used throughout LoanMovementService.
    private const LOAN_STATUS_FILTER =
        "(TRIM(COALESCE(loan_listings.loan_status, '')) = '' OR loan_listings.loan_status IN ('NORM', 'Normal', 'OAEM', 'SUBS', 'Watch'))";

    public function __construct(private readonly LoanMovementService $loans) {}

    /**
     * Build the full weekly report dataset — combined KES-equivalent (LCY+FCY)
     * via loan_book_outstanding, matching LoanMovementService::buildCombined().
     *
     * @return array{periods: array, segments: array}
     */
    public function build(string $weekEnd): array
    {
        $weekEnd   = Carbon::parse($weekEnd)->toDateString();
        $weekStart = $this->findWeekStart($weekEnd);
        $mtdStart  = $this->findMtdStart($weekEnd);

        return [
            'periods' => [
                'week_start' => $weekStart,
                'week_end'   => $weekEnd,
                'mtd_start'  => $mtdStart,
            ],
            'segments' => $this->buildSegmentData($weekStart, $weekEnd, $mtdStart),
        ];
    }

    /**
     * Persist a build() result into weekly_loan_snapshots.
     * Replaces any existing rows for the same report_date.
     *
     * @return int rows inserted
     */
    public function persist(array $data): int
    {
        $periods = $data['periods'];
        $weekEnd = $periods['week_end'];

        WeeklyLoanSnapshot::where('report_date', $weekEnd)->delete();

        $rows = [];
        $now  = now();

        foreach ($data['segments'] ?? [] as $seg) {
            $code = (string) ($seg['code'] ?? 'UNMAPPED');

            $rows[] = $this->buildRow($periods, $code, '', $seg, $now);

            foreach ($seg['sub_segments'] ?? [] as $sub) {
                $rows[] = $this->buildRow($periods, $code, (string) ($sub['name'] ?? ''), $sub, $now);
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            WeeklyLoanSnapshot::insert($chunk);
        }

        return count($rows);
    }

    /**
     * Load a previously persisted build() result from weekly_loan_snapshots.
     * Returns null if no data exists for weekEnd.
     */
    public function loadFromTable(string $weekEnd): ?array
    {
        $rows = WeeklyLoanSnapshot::where('report_date', $weekEnd)
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
            ],
            'segments' => $this->reconstructSegments($rows),
        ];
    }

    // -------------------------------------------------------------------------
    // Persist helpers
    // -------------------------------------------------------------------------

    private function buildRow(array $periods, string $code, string $subName, array $data, \Carbon\Carbon $now): array
    {
        return [
            'report_date'      => $periods['week_end'],
            'week_start'       => $periods['week_start'],
            'mtd_start'        => $periods['mtd_start'],
            'segment_code'     => $code,
            'sub_segment_name' => $subName,
            'weekly_mv'        => (float) ($data['weekly_mv']   ?? 0),
            'mtd_mv'           => (float) ($data['mtd_mv']      ?? 0),
            'total_loans'      => (float) ($data['total_loans'] ?? 0),
            'created_at'       => $now,
            'updated_at'       => $now,
        ];
    }

    private function reconstructSegments(Collection $rows): array
    {
        $order      = self::SEGMENT_ORDER + ['ALL' => 99];
        $segRows    = $rows->where('sub_segment_name', '');
        $subRows    = $rows->where('sub_segment_name', '!=', '');
        $subByCode  = $subRows->groupBy('segment_code');

        $segments = [];

        foreach ($segRows as $row) {
            $code = $row->segment_code;

            $subSegments = [];
            foreach ($subByCode->get($code, collect()) as $sub) {
                $subSegments[] = [
                    'name'        => $sub->sub_segment_name,
                    'weekly_mv'   => (float) $sub->weekly_mv,
                    'mtd_mv'      => (float) $sub->mtd_mv,
                    'total_loans' => (float) $sub->total_loans,
                ];
            }

            usort($subSegments, fn($a, $b) => $a['name'] <=> $b['name']);

            $segments[$code] = [
                'code'         => $code,
                'name'         => self::SEGMENT_MAP[$code] ?? $code,
                'weekly_mv'    => (float) $row->weekly_mv,
                'mtd_mv'       => (float) $row->mtd_mv,
                'total_loans'  => (float) $row->total_loans,
                'sub_segments' => $subSegments,
            ];
        }

        uasort($segments, fn($a, $b) => ($order[$a['code']] ?? 50) <=> ($order[$b['code']] ?? 50));

        return array_values($segments);
    }

    /**
     * Top weekly movers per sub-segment for the Excel drilldown sheet
     * (combined LCY+FCY, KES-equivalent via loan_book_outstanding).
     *
     * @return array<string, array{gainers: Collection, losers: Collection}>  keyed by sub-segment name
     */
    public function drilldown(string $weekStart, string $weekEnd, int $limit = 100): array
    {
        $rows = $this->fetchCifMovementRows($weekStart, $weekEnd);

        $result = [];

        foreach (collect($rows)->groupBy('sub_segment_name') as $subSegName => $subRows) {
            $gainers = collect($subRows)
                ->filter(fn($r) => (float) $r->movement > 0)
                ->sortByDesc(fn($r) => (float) $r->movement)
                ->take($limit)
                ->values();

            $losers = collect($subRows)
                ->filter(fn($r) => (float) $r->movement < 0)
                ->sortBy(fn($r) => (float) $r->movement)
                ->take($limit)
                ->values();

            $result[(string) $subSegName] = [
                'gainers' => $gainers,
                'losers'  => $losers,
            ];
        }

        ksort($result);
        return $result;
    }

    /**
     * Overall (cross sub-segment) top movers for a given period.
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
     * Per-CIF combined (LCY+FCY, KES-equivalent) loan movement between two dates,
     * joined to its sub-segment name. Shared by drilldown() and topMovers().
     */
    private function fetchCifMovementRows(string $start, string $end): array
    {
        $cifBiz = $this->loans->cifBusinessSubquery();
        $cifSub = $this->cifSubSegmentSubquery();

        $rows = DB::table('loan_listings')
            ->leftJoinSub($cifBiz, 'csm', fn($j) => $j->on('csm.cif', '=', 'loan_listings.cif'))
            ->leftJoinSub($cifSub, 'css', fn($j) => $j->on('css.cif', '=', 'loan_listings.cif'))
            ->selectRaw('loan_listings.cif, MAX(loan_listings.name) as customer_name, MAX(loan_listings.branch) as branch_code')
            ->selectRaw("COALESCE(NULLIF(TRIM(MAX(css.sub_segment_name)), ''), 'Unmapped') as sub_segment_name")
            ->selectRaw('SUM(CASE WHEN loan_listings.as_at_date = ? THEN loan_book_outstanding ELSE 0 END) as start_balance', [$start])
            ->selectRaw('SUM(CASE WHEN loan_listings.as_at_date = ? THEN loan_book_outstanding ELSE 0 END) as end_balance', [$end])
            ->whereIn('loan_listings.as_at_date', [$start, $end])
            ->whereNotNull('loan_listings.cif')
            ->whereRaw(self::LOAN_STATUS_FILTER)
            ->groupBy('loan_listings.cif')
            ->havingRaw('SUM(CASE WHEN loan_listings.as_at_date = ? THEN loan_book_outstanding ELSE 0 END) - SUM(CASE WHEN loan_listings.as_at_date = ? THEN loan_book_outstanding ELSE 0 END) <> 0', [$end, $start])
            ->get();

        return $rows->map(function ($r) {
            $r->movement = (float) $r->end_balance - (float) $r->start_balance;
            return $r;
        })->all();
    }

    // -------------------------------------------------------------------------
    // Private builders
    // -------------------------------------------------------------------------

    private function buildSegmentData(string $weekStart, string $weekEnd, string $mtdStart): array
    {
        $rows = $this->querySegmentSubTotals($weekStart, $weekEnd, $mtdStart);

        $bySegment = collect($rows)->groupBy('business_segment');

        $segments = [];
        $totals   = ['ws' => 0.0, 'we' => 0.0, 'ms' => 0.0];

        $allSegments = array_unique(array_merge(array_keys(self::SEGMENT_ORDER), $bySegment->keys()->all()));

        foreach ($allSegments as $code) {
            $subRows = $bySegment->get($code);
            if (!$subRows) continue;

            $subSegments = [];
            $segStart = $segEnd = $segMtd = 0.0;

            foreach ($subRows as $row) {
                $subName = trim((string) ($row->sub_segment_name ?? 'Unmapped')) ?: 'Unmapped';

                $ws = (float) ($row->weekly_start ?? 0);
                $we = (float) ($row->weekly_end   ?? 0);
                $ms = (float) ($row->mtd_start    ?? 0);

                $segStart += $ws;
                $segEnd   += $we;
                $segMtd   += $ms;

                $subSegments[] = [
                    'name'        => $subName,
                    'weekly_mv'   => $we - $ws,
                    'mtd_mv'      => $we - $ms,
                    'total_loans' => $we,
                ];
            }

            usort($subSegments, fn($a, $b) => $a['name'] <=> $b['name']);

            $totals['ws'] += $segStart;
            $totals['we'] += $segEnd;
            $totals['ms'] += $segMtd;

            $segments[$code] = [
                'code'         => $code,
                'name'         => self::SEGMENT_MAP[$code] ?? $code,
                'weekly_mv'    => $segEnd - $segStart,
                'mtd_mv'       => $segEnd - $segMtd,
                'total_loans'  => $segEnd,
                'sub_segments' => $subSegments,
            ];
        }

        uasort($segments, fn($a, $b) =>
            (self::SEGMENT_ORDER[$a['code']] ?? 50) <=> (self::SEGMENT_ORDER[$b['code']] ?? 50)
        );

        $segments['ALL'] = [
            'code'         => 'ALL',
            'name'         => 'Totals',
            'weekly_mv'    => $totals['we'] - $totals['ws'],
            'mtd_mv'       => $totals['we'] - $totals['ms'],
            'total_loans'  => $totals['we'],
            'sub_segments' => [],
        ];

        return array_values($segments);
    }

    /**
     * Segment + sub-segment totals at 3 dates (week start/end, MTD start) —
     * combined KES-equivalent (LCY+FCY) via loan_book_outstanding, no currency filter.
     * Sub-segment is the same sub_segment_mappings.business_segment_name deposits use.
     */
    private function querySegmentSubTotals(
        string $weekStart,
        string $weekEnd,
        string $mtdStart
    ): \Illuminate\Support\Collection {
        $dates  = array_values(array_unique([$weekStart, $weekEnd, $mtdStart]));
        $cifBiz = $this->loans->cifBusinessSubquery();
        $cifSub = $this->cifSubSegmentSubquery();

        return DB::table('loan_listings')
            ->leftJoinSub($cifBiz, 'csm', fn($j) => $j->on('csm.cif', '=', 'loan_listings.cif'))
            ->leftJoinSub($cifSub, 'css', fn($j) => $j->on('css.cif', '=', 'loan_listings.cif'))
            ->selectRaw($this->loans->segmentExpr() . ' AS business_segment')
            ->selectRaw("COALESCE(NULLIF(TRIM(css.sub_segment_name), ''), 'Unmapped') AS sub_segment_name")
            ->selectRaw('SUM(CASE WHEN loan_listings.as_at_date = ? THEN loan_book_outstanding ELSE 0 END) as weekly_start', [$weekStart])
            ->selectRaw('SUM(CASE WHEN loan_listings.as_at_date = ? THEN loan_book_outstanding ELSE 0 END) as weekly_end', [$weekEnd])
            ->selectRaw('SUM(CASE WHEN loan_listings.as_at_date = ? THEN loan_book_outstanding ELSE 0 END) as mtd_start', [$mtdStart])
            ->whereIn('loan_listings.as_at_date', $dates)
            ->whereRaw(self::LOAN_STATUS_FILTER)
            ->groupBy(DB::raw($this->loans->segmentExpr()), DB::raw("COALESCE(NULLIF(TRIM(css.sub_segment_name), ''), 'Unmapped')"))
            ->get();
    }

    /**
     * Returns one canonical row per CIF: (cif, sub_segment_name) sourced from
     * sub_segment_mappings.business_segment_name — the same field deposits use.
     * MAX(...) deduplicates CIFs that map to multiple MIS codes.
     */
    private function cifSubSegmentSubquery()
    {
        return DB::table('customer_accounts_imports as cai')
            ->join('sub_segment_mappings as sm', 'sm.mis_code', '=', DB::raw('TRIM(cai.etibiseg2)'))
            ->selectRaw('cai.f12_cif AS cif, MAX(sm.business_segment_name) AS sub_segment_name')
            ->whereNotNull('cai.f12_cif')
            ->whereNotNull('cai.etibiseg2')
            ->whereRaw("TRIM(cai.etibiseg2) <> ''")
            ->groupBy('cai.f12_cif');
    }

    // -------------------------------------------------------------------------
    // Date helpers (mirrors WeeklySegmentReportService, sourced from loan_listings.as_at_date)
    // -------------------------------------------------------------------------

    /** Most recent as_at_date available in loan_listings. */
    public function findLatestBalanceDate(): string
    {
        $d = DB::table('loan_listings')->max('as_at_date');
        return $d ? Carbon::parse((string) $d)->toDateString() : now()->timezone('Africa/Nairobi')->toDateString();
    }

    /** Latest as_at_date on or before (weekEnd − 7 days). */
    private function findWeekStart(string $weekEnd): string
    {
        $target = Carbon::parse($weekEnd)->subDays(7)->toDateString();

        $d = DB::table('loan_listings')
            ->where('as_at_date', '<=', $target)
            ->max('as_at_date');

        return $d ? Carbon::parse((string) $d)->toDateString() : $target;
    }

    /** Latest as_at_date in the previous calendar month. */
    private function findMtdStart(string $weekEnd): string
    {
        $prevMonthEnd = Carbon::parse($weekEnd)->startOfMonth()->subDay();

        $d = DB::table('loan_listings')
            ->whereYear('as_at_date', $prevMonthEnd->year)
            ->whereMonth('as_at_date', $prevMonthEnd->month)
            ->max('as_at_date');

        if ($d) return Carbon::parse((string) $d)->toDateString();

        $d2 = DB::table('loan_listings')
            ->where('as_at_date', '<', Carbon::parse($weekEnd)->startOfMonth()->toDateString())
            ->max('as_at_date');

        return $d2 ? Carbon::parse((string) $d2)->toDateString() : $weekEnd;
    }
}
