<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LoanMovementService
{
    // Display order — values should match sub_segment_mappings.business in your DB
    private const SEGMENT_ORDER = ['Corporate Banking', 'Commercial Banking', 'Consumer Banking'];

    // Canonical status-bucket display order within each segment
    private const BUCKET_ORDER = ['Performing', 'Watch', 'Substandard', 'Doubtful', 'Loss', 'Other'];

    /**
     * Build loan book movement data for the email report.
     *
     * Returns:
     * [
     *   'segments' => [
     *     [
     *       'name'       => 'Corporate Banking',
     *       'categories' => [
     *         ['name' => 'Performing', 'startBalance' => 0, 'endBalance' => 0,
     *          'weekOnWeek' => 0, 'mtd' => 0, 'ytd' => 0, 'direction' => 'GAIN|LOSS|FLAT'],
     *         ...
     *       ],
     *       'startBalance' => 0, 'endBalance' => 0, 'weekOnWeek' => 0,
     *       'mtd' => 0, 'ytd' => 0, 'direction' => 'GAIN|LOSS|FLAT'
     *     ],
     *     ...
     *   ],
     *   'totals' => ['startBalance' => 0, 'endBalance' => 0, 'weekOnWeek' => 0, 'mtd' => 0, 'ytd' => 0]
     * ]
     */
    public function build(string $start, string $end, string $currencyType = 'LCY'): array
    {
        $startDate = Carbon::parse($start)->toDateString();
        $endDate   = Carbon::parse($end)->toDateString();
        $wodStart  = Carbon::parse($end)->subWeek()->toDateString();

        // MTD/YTD anchors resolve to the latest available snapshot on or before
        // month-end/year-end (e.g. 2025-12-30, since 2025-12-31 has no loan_listings
        // row) — an exact-date match here would silently zero out the whole
        // MTD/YTD column whenever the calendar boundary falls on a non-import date.
        // Mirrors LoanDashboardService's latestAvailableDateOnOrBefore() and the
        // deposits side (FinanceHomeController::resolveEoyBaselineClosing()).
        $mtdStart = $this->resolveDateOnOrBefore(Carbon::parse($end)->startOfMonth()->subDay()->toDateString())
            ?? Carbon::parse($end)->startOfMonth()->toDateString();
        $ytdStart = $this->resolveDateOnOrBefore(Carbon::parse($end)->startOfYear()->subDay()->toDateString())
            ?? Carbon::parse($end)->startOfYear()->toDateString();

        $currencyType = strtoupper($currencyType);
        $dates        = array_unique([$startDate, $endDate, $wodStart, $mtdStart, $ytdStart]);
        $amountCol    = ($currencyType === 'LCY') ? 'outstanding_amount_lcy' : 'loan_book_outstanding';

        $cifBiz = $this->cifBusinessSubquery();

        $rows = DB::table('loan_listings')
            ->leftJoinSub($cifBiz, 'csm', fn($j) => $j->on('csm.cif', '=', 'loan_listings.cif'))
            ->selectRaw($this->segmentExpr() . " AS business_segment, loan_listings.status_bucket")
            ->selectRaw("SUM(CASE WHEN loan_listings.as_at_date = ? THEN {$amountCol} ELSE 0 END) as start_bal",  [$startDate])
            ->selectRaw("SUM(CASE WHEN loan_listings.as_at_date = ? THEN {$amountCol} ELSE 0 END) as end_bal",    [$endDate])
            ->selectRaw("SUM(CASE WHEN loan_listings.as_at_date = ? THEN {$amountCol} ELSE 0 END) as wod_start",  [$wodStart])
            ->selectRaw("SUM(CASE WHEN loan_listings.as_at_date = ? THEN {$amountCol} ELSE 0 END) as mtd_start",  [$mtdStart])
            ->selectRaw("SUM(CASE WHEN loan_listings.as_at_date = ? THEN {$amountCol} ELSE 0 END) as ytd_start",  [$ytdStart])
            ->whereIn('loan_listings.as_at_date', $dates)
            ->where('loan_listings.currency_type', $currencyType)
            ->whereRaw("(TRIM(COALESCE(loan_listings.loan_status, '')) = '' OR loan_listings.loan_status IN ('NORM', 'Normal', 'OAEM', 'SUBS', 'Watch'))")
            ->groupBy(DB::raw($this->segmentExpr()), 'loan_listings.status_bucket')
            ->get();

        $data = [];
        foreach ($rows as $r) {
            $seg    = trim($r->business_segment ?? 'UNMAPPED');
            $bucket = trim($r->status_bucket ?? 'Other');

            if (!isset($data[$seg])) {
                $data[$seg] = [];
            }

            $data[$seg][$bucket] = [
                'startBalance' => (float) ($r->start_bal ?? 0),
                'endBalance'   => (float) ($r->end_bal   ?? 0),
                'weekOnWeek'   => (float) ($r->end_bal   ?? 0) - (float) ($r->wod_start ?? 0),
                'mtd'          => (float) ($r->end_bal   ?? 0) - (float) ($r->mtd_start ?? 0),
                'ytd'          => (float) ($r->end_bal   ?? 0) - (float) ($r->ytd_start ?? 0),
            ];
        }

        $allSegments = array_unique(array_merge(self::SEGMENT_ORDER, array_keys($data)));
        $segments    = [];

        $grandStart = $grandEnd = $grandWow = $grandMtd = $grandYtd = 0;

        foreach ($allSegments as $segName) {
            if (!isset($data[$segName])) continue;

            $buckets    = $data[$segName];
            $categories = [];
            $segStart = $segEnd = $segWow = $segMtd = $segYtd = 0;

            $allBuckets = array_unique(array_merge(self::BUCKET_ORDER, array_keys($buckets)));

            foreach ($allBuckets as $bucketName) {
                if (!isset($buckets[$bucketName])) continue;

                $b = $buckets[$bucketName];
                $categories[] = [
                    'name'         => $bucketName,
                    'startBalance' => $b['startBalance'],
                    'endBalance'   => $b['endBalance'],
                    'weekOnWeek'   => $b['weekOnWeek'],
                    'mtd'          => $b['mtd'],
                    'ytd'          => $b['ytd'],
                    'direction'    => $this->direction($b['endBalance'] - $b['startBalance']),
                ];

                $segStart += $b['startBalance'];
                $segEnd   += $b['endBalance'];
                $segWow   += $b['weekOnWeek'];
                $segMtd   += $b['mtd'];
                $segYtd   += $b['ytd'];
            }

            $segments[] = [
                'name'         => $segName,
                'categories'   => $categories,
                'startBalance' => $segStart,
                'endBalance'   => $segEnd,
                'weekOnWeek'   => $segWow,
                'mtd'          => $segMtd,
                'ytd'          => $segYtd,
                'direction'    => $this->direction($segEnd - $segStart),
            ];

            $grandStart += $segStart;
            $grandEnd   += $segEnd;
            $grandWow   += $segWow;
            $grandMtd   += $segMtd;
            $grandYtd   += $segYtd;
        }

        return [
            'segments' => $segments,
            'totals'   => [
                'startBalance' => $grandStart,
                'endBalance'   => $grandEnd,
                'weekOnWeek'   => $grandWow,
                'mtd'          => $grandMtd,
                'ytd'          => $grandYtd,
                'direction'    => $this->direction($grandEnd - $grandStart),
            ],
        ];
    }

    /**
     * Status-bucket breakdown (Performing/Watch/Substandard/Doubtful/Loss) for
     * a single business segment, combining LCY and FCY via loan_book_outstanding
     * — the segment-page analog of build()'s per-segment category breakdown,
     * pre-filtered to one segment instead of looping over all of them.
     */
    public function buildStatusBreakdownForSegment(string $start, string $end, string $segmentCanon): array
    {
        $startDate = Carbon::parse($start)->toDateString();
        $endDate   = Carbon::parse($end)->toDateString();
        $wodStart  = Carbon::parse($end)->subWeek()->toDateString();

        // See build()'s comment: resolve to the latest available snapshot on or
        // before month-end/year-end (e.g. 2025-12-30), not an exact-date match.
        $mtdStart = $this->resolveDateOnOrBefore(Carbon::parse($end)->startOfMonth()->subDay()->toDateString())
            ?? Carbon::parse($end)->startOfMonth()->toDateString();
        $ytdStart = $this->resolveDateOnOrBefore(Carbon::parse($end)->startOfYear()->subDay()->toDateString())
            ?? Carbon::parse($end)->startOfYear()->toDateString();

        $dates  = array_unique([$startDate, $endDate, $wodStart, $mtdStart, $ytdStart]);
        $cifBiz = $this->cifBusinessSubquery();

        $rows = DB::table('loan_listings')
            ->leftJoinSub($cifBiz, 'csm', fn($j) => $j->on('csm.cif', '=', 'loan_listings.cif'))
            ->selectRaw('loan_listings.status_bucket AS status_bucket')
            ->selectRaw('SUM(CASE WHEN loan_listings.as_at_date = ? THEN loan_book_outstanding ELSE 0 END) as start_bal', [$startDate])
            ->selectRaw('SUM(CASE WHEN loan_listings.as_at_date = ? THEN loan_book_outstanding ELSE 0 END) as end_bal',   [$endDate])
            ->selectRaw('SUM(CASE WHEN loan_listings.as_at_date = ? THEN loan_book_outstanding ELSE 0 END) as wod_start', [$wodStart])
            ->selectRaw('SUM(CASE WHEN loan_listings.as_at_date = ? THEN loan_book_outstanding ELSE 0 END) as mtd_start', [$mtdStart])
            ->selectRaw('SUM(CASE WHEN loan_listings.as_at_date = ? THEN loan_book_outstanding ELSE 0 END) as ytd_start', [$ytdStart])
            ->whereIn('loan_listings.as_at_date', $dates)
            ->whereRaw($this->segmentExpr() . ' = ?', [$segmentCanon])
            ->groupBy('loan_listings.status_bucket')
            ->get();

        $buckets = [];
        foreach ($rows as $r) {
            $bucket = trim((string) ($r->status_bucket ?? '')) ?: 'Other';

            if (!isset($buckets[$bucket])) {
                $buckets[$bucket] = ['startBalance' => 0.0, 'endBalance' => 0.0, 'weekOnWeek' => 0.0, 'mtd' => 0.0, 'ytd' => 0.0];
            }

            $buckets[$bucket]['startBalance'] += (float) $r->start_bal;
            $buckets[$bucket]['endBalance']   += (float) $r->end_bal;
            $buckets[$bucket]['weekOnWeek']   += (float) $r->end_bal - (float) $r->wod_start;
            $buckets[$bucket]['mtd']          += (float) $r->end_bal - (float) $r->mtd_start;
            $buckets[$bucket]['ytd']          += (float) $r->end_bal - (float) $r->ytd_start;
        }

        $allBuckets = array_unique(array_merge(self::BUCKET_ORDER, array_keys($buckets)));
        $categories = [];
        $segStart = $segEnd = $segWow = $segMtd = $segYtd = 0.0;

        foreach ($allBuckets as $bucketName) {
            if (!isset($buckets[$bucketName])) continue;

            $b = $buckets[$bucketName];
            $categories[] = [
                'name'         => $bucketName,
                'startBalance' => $b['startBalance'],
                'endBalance'   => $b['endBalance'],
                'weekOnWeek'   => $b['weekOnWeek'],
                'mtd'          => $b['mtd'],
                'ytd'          => $b['ytd'],
                'direction'    => $this->direction($b['endBalance'] - $b['startBalance']),
            ];

            $segStart += $b['startBalance'];
            $segEnd   += $b['endBalance'];
            $segWow   += $b['weekOnWeek'];
            $segMtd   += $b['mtd'];
            $segYtd   += $b['ytd'];
        }

        return [
            'categories'   => $categories,
            'startBalance' => $segStart,
            'endBalance'   => $segEnd,
            'weekOnWeek'   => $segWow,
            'mtd'          => $segMtd,
            'ytd'          => $segYtd,
            'direction'    => $this->direction($segEnd - $segStart),
        ];
    }

    /**
     * Combined segment overview with separate LCY and FCY movement columns.
     * Both currency types use loan_book_outstanding as the KES-equivalent amount.
     *
     * $segmentFilter, when given, restricts everything (including the movers
     * list) to that one canonical segment — used by the segment drill-down page.
     */
    public function buildCombined(string $start, string $end, int $moverLimit = 10, ?string $segmentFilter = null): array
    {
        $startDate = Carbon::parse($start)->toDateString();
        $endDate   = Carbon::parse($end)->toDateString();

        $cifBiz  = $this->cifBusinessSubquery();
        $buckets = [];

        foreach (['LCY', 'FCY'] as $ctype) {
            $rows = DB::table('loan_listings')
                ->leftJoinSub($cifBiz, 'csm', fn($j) => $j->on('csm.cif', '=', 'loan_listings.cif'))
                ->selectRaw($this->segmentExpr() . " AS business_segment")
                ->selectRaw('SUM(CASE WHEN loan_listings.as_at_date = ? THEN loan_book_outstanding ELSE 0 END) as start_bal', [$startDate])
                ->selectRaw('SUM(CASE WHEN loan_listings.as_at_date = ? THEN loan_book_outstanding ELSE 0 END) as end_bal',   [$endDate])
                ->whereIn('loan_listings.as_at_date', [$startDate, $endDate])
                ->where('loan_listings.currency_type', $ctype)
                ->whereRaw("(TRIM(COALESCE(loan_listings.loan_status, '')) = '' OR loan_listings.loan_status IN ('NORM', 'Normal', 'OAEM', 'SUBS', 'Watch'))")
                ->when($segmentFilter, fn($q) => $q->whereRaw($this->segmentExpr() . ' = ?', [$segmentFilter]))
                ->groupBy(DB::raw($this->segmentExpr()))
                ->get();

            foreach ($rows as $r) {
                $display = trim((string) ($r->business_segment ?? 'UNMAPPED'));

                if (!isset($buckets[$display])) {
                    $buckets[$display] = ['lcyStart' => 0.0, 'lcyEnd' => 0.0, 'fcyStart' => 0.0, 'fcyEnd' => 0.0];
                }

                if ($ctype === 'LCY') {
                    $buckets[$display]['lcyStart'] += (float) $r->start_bal;
                    $buckets[$display]['lcyEnd']   += (float) $r->end_bal;
                } else {
                    $buckets[$display]['fcyStart'] += (float) $r->start_bal;
                    $buckets[$display]['fcyEnd']   += (float) $r->end_bal;
                }
            }
        }

        $segments = [];
        $gLcyS = $gLcyE = $gFcyS = $gFcyE = 0.0;

        $makeSegment = function(string $name, array $b): array {
            $startBal = $b['lcyStart'] + $b['fcyStart'];
            $endBal   = $b['lcyEnd']   + $b['fcyEnd'];
            $lcyMv    = $b['lcyEnd']   - $b['lcyStart'];
            $fcyMv    = $b['fcyEnd']   - $b['fcyStart'];
            $netMv    = $endBal - $startBal;
            return [
                'name'         => $name,
                'startBalance' => $startBal,
                'endBalance'   => $endBal,
                'lcyMovement'  => $lcyMv,
                'fcyMovement'  => $fcyMv,
                'movement'     => $netMv,
                'direction'    => $this->direction($netMv),
            ];
        };

        // Known segments first (in defined order)
        foreach (self::SEGMENT_ORDER as $seg) {
            if (!isset($buckets[$seg])) continue;
            $segments[] = $makeSegment($seg, $buckets[$seg]);
            $gLcyS += $buckets[$seg]['lcyStart']; $gLcyE += $buckets[$seg]['lcyEnd'];
            $gFcyS += $buckets[$seg]['fcyStart']; $gFcyE += $buckets[$seg]['fcyEnd'];
        }

        // Any segments from DB not in SEGMENT_ORDER, before UNMAPPED
        foreach ($buckets as $seg => $b) {
            if (in_array($seg, self::SEGMENT_ORDER, true) || $seg === 'UNMAPPED') continue;
            $segments[] = $makeSegment($seg, $b);
            $gLcyS += $b['lcyStart']; $gLcyE += $b['lcyEnd'];
            $gFcyS += $b['fcyStart']; $gFcyE += $b['fcyEnd'];
        }

        // UNMAPPED last (only if it has balances)
        if (isset($buckets['UNMAPPED'])) {
            $b = $buckets['UNMAPPED'];
            if ($b['lcyStart'] + $b['lcyEnd'] + $b['fcyStart'] + $b['fcyEnd'] > 0) {
                $segments[] = $makeSegment('UNMAPPED', $b);
                $gLcyS += $b['lcyStart']; $gLcyE += $b['lcyEnd'];
                $gFcyS += $b['fcyStart']; $gFcyE += $b['fcyEnd'];
            }
        }

        $grandStart = $gLcyS + $gFcyS;
        $grandEnd   = $gLcyE + $gFcyE;
        $grandMv    = $grandEnd - $grandStart;

        return [
            'segments' => $segments,
            'totals'   => [
                'startBalance'   => $grandStart,
                'endBalance'     => $grandEnd,
                'lcyMovement'    => $gLcyE - $gLcyS,
                'fcyMovement'    => $gFcyE - $gFcyS,
                'movement'       => $grandMv,
                'direction'      => $this->direction($grandMv),
                'bankTotalStart' => $this->totalLoanBook($startDate),
                'bankTotalEnd'   => $this->totalLoanBook($endDate),
            ],
            'movers' => $this->topMoversCombined($startDate, $endDate, $moverLimit, $segmentFilter),
        ];
    }

    /**
     * Total loan book for the entire bank as at a given date — every loan
     * regardless of status_bucket (Performing, Watch, Substandard, Doubtful, Loss).
     * Unlike buildCombined()'s totals, this is not filtered to performing loans.
     */
    private function totalLoanBook(string $asAtDate): float
    {
        return (float) DB::table('loan_listings')
            ->where('as_at_date', $asAtDate)
            ->sum('loan_book_outstanding');
    }

    private function topMoversCombined(string $startDate, string $endDate, int $limit, ?string $segmentFilter = null): array
    {
        $startExpr = 'SUM(CASE WHEN loan_listings.as_at_date = ? THEN loan_book_outstanding ELSE 0 END)';
        $endExpr   = 'SUM(CASE WHEN loan_listings.as_at_date = ? THEN loan_book_outstanding ELSE 0 END)';
        $moveExpr  = "({$endExpr}) - ({$startExpr})";

        $cifBiz = $this->cifBusinessSubquery();

        $base = DB::table('loan_listings')
            ->leftJoinSub($cifBiz, 'csm', fn($j) => $j->on('csm.cif', '=', 'loan_listings.cif'))
            ->selectRaw("loan_listings.cif, MAX(loan_listings.name) as name, MAX(loan_listings.branch) as branch, MAX(" . $this->segmentExpr() . ") as business_segment")
            ->selectRaw("{$startExpr} as start_balance", [$startDate])
            ->selectRaw("{$endExpr}   as end_balance",   [$endDate])
            ->selectRaw("{$moveExpr}  as movement",      [$endDate, $startDate])
            ->whereIn('loan_listings.as_at_date', [$startDate, $endDate])
            ->whereRaw("(TRIM(COALESCE(loan_listings.loan_status, '')) = '' OR loan_listings.loan_status IN ('NORM', 'Normal', 'OAEM', 'SUBS', 'Watch'))")
            ->when($segmentFilter, fn($q) => $q->whereRaw($this->segmentExpr() . ' = ?', [$segmentFilter]))
            ->groupBy('loan_listings.cif')
            ->havingRaw("{$moveExpr} <> 0", [$endDate, $startDate]);

        $gainers = (clone $base)
            ->havingRaw("{$moveExpr} > 0", [$endDate, $startDate])
            ->orderByDesc('movement')
            ->limit($limit)
            ->get()->toArray();

        $losers = (clone $base)
            ->havingRaw("{$moveExpr} < 0", [$endDate, $startDate])
            ->orderBy('movement')
            ->limit($limit)
            ->get()->toArray();

        return ['gainers' => $gainers, 'losers' => $losers];
    }

    /**
     * Top individual loan accounts by movement (gainers = accounts that grew,
     * losers = accounts that shrank) between two dates.
     */
    public function topMovers(string $start, string $end, string $currencyType = 'LCY', int $limit = 20): array
    {
        $startDate    = Carbon::parse($start)->toDateString();
        $endDate      = Carbon::parse($end)->toDateString();
        $currencyType = strtoupper($currencyType);
        $amountCol    = ($currencyType === 'LCY') ? 'outstanding_amount_lcy' : 'loan_book_outstanding';

        $startExpr = "SUM(CASE WHEN loan_listings.as_at_date = ? THEN {$amountCol} ELSE 0 END)";
        $endExpr   = "SUM(CASE WHEN loan_listings.as_at_date = ? THEN {$amountCol} ELSE 0 END)";
        $moveExpr  = "({$endExpr}) - ({$startExpr})";

        $cifBiz = $this->cifBusinessSubquery();

        $base = DB::table('loan_listings')
            ->leftJoinSub($cifBiz, 'csm', fn($j) => $j->on('csm.cif', '=', 'loan_listings.cif'))
            ->selectRaw("loan_listings.cif, MAX(loan_listings.name) as name, MAX(loan_listings.branch) as branch, MAX(" . $this->segmentExpr() . ") as business_segment")
            ->selectRaw("{$startExpr} as start_balance", [$startDate])
            ->selectRaw("{$endExpr}   as end_balance",   [$endDate])
            ->selectRaw("{$moveExpr}  as movement",      [$endDate, $startDate])
            ->whereIn('loan_listings.as_at_date', [$startDate, $endDate])
            ->where('loan_listings.currency_type', $currencyType)
            ->whereRaw("(TRIM(COALESCE(loan_listings.loan_status, '')) = '' OR loan_listings.loan_status IN ('NORM', 'Normal', 'OAEM', 'SUBS', 'Watch'))")
            ->groupBy('loan_listings.cif')
            ->havingRaw("{$moveExpr} <> 0", [$endDate, $startDate]);

        $gainers = (clone $base)
            ->havingRaw("{$moveExpr} > 0", [$endDate, $startDate])
            ->orderByDesc('movement')
            ->limit($limit)
            ->get()
            ->map(fn($r) => array_merge((array) $r, ['direction' => 'GAIN']));

        $losers = (clone $base)
            ->havingRaw("{$moveExpr} < 0", [$endDate, $startDate])
            ->orderBy('movement')
            ->limit($limit)
            ->get()
            ->map(fn($r) => array_merge((array) $r, ['direction' => 'LOSS']));

        return [
            'gainers' => $gainers->values()->all(),
            'losers'  => $losers->values()->all(),
        ];
    }

    /**
     * Returns one canonical row per CIF: (cif, business) sourced from sub_segment_mappings.
     * MAX(sm.business) deduplicates CIFs that map to multiple MIS codes.
     * Mirrors the pattern used by SubSegmentMoversService::cifMisCodeSubquery().
     */
    public function cifBusinessSubquery()
    {
        return DB::table('customer_accounts_imports as cai')
            ->join('sub_segment_mappings as sm', 'sm.mis_code', '=', DB::raw("TRIM(cai.etibiseg2)"))
            ->selectRaw("cai.f12_cif AS cif, MAX(sm.business) AS business")
            ->whereNotNull('cai.f12_cif')
            ->whereNotNull('cai.etibiseg2')
            ->whereRaw("TRIM(cai.etibiseg2) <> ''")
            ->groupBy('cai.f12_cif');
    }

    /**
     * SQL expression that resolves a loan's business segment in priority order:
     *   1. Canonical value from sub_segment_mappings (csm.business)
     *   2. Normalized raw value from loan_listings.business_segment
     *   3. 'UNMAPPED' as final fallback
     *
     * Normalization maps partial raw labels (e.g. "COMMERCIAL") to the full
     * canonical label (e.g. "COMMERCIAL BANKING") used throughout the system.
     */
    public function segmentExpr(): string
    {
        return "COALESCE(csm.business, CASE
            WHEN UPPER(loan_listings.source_type) = 'CREDIT_CARD'               THEN 'CONSUMER BANKING'
            WHEN UPPER(TRIM(loan_listings.business_segment)) LIKE '%CORPORATE%'  THEN 'CORPORATE BANKING'
            WHEN UPPER(TRIM(loan_listings.business_segment)) LIKE '%COMMERCIAL%'
              OR UPPER(TRIM(loan_listings.business_segment)) LIKE '%COMERCIAL%'  THEN 'COMMERCIAL BANKING'
            WHEN UPPER(TRIM(loan_listings.business_segment)) LIKE '%CONSUMER%'   THEN 'CONSUMER BANKING'
            ELSE loan_listings.business_segment
        END, 'UNMAPPED')";
    }

    private function direction(float $movement): string
    {
        if ($movement > 0) return 'GAIN';
        if ($movement < 0) return 'LOSS';
        return 'FLAT';
    }

    /**
     * Latest loan_listings snapshot date on or before $targetDate, or null if
     * none exists yet (e.g. a YTD target before the table's earliest import).
     */
    private function resolveDateOnOrBefore(string $targetDate): ?string
    {
        $date = DB::table('loan_listings')
            ->where('as_at_date', '<=', $targetDate)
            ->max('as_at_date');

        return $date ? Carbon::parse((string) $date)->toDateString() : null;
    }
}
