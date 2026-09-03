<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Builds the historical trend data behind the Loan Book Dashboard
 * (resources/views/finance/loans/pipeline.blade.php).
 *
 * Unlike deposits (FinanceHomeController), loans has no precomputed
 * daily history table — this queries loan_listings directly across
 * a date range, since one snapshot row-set already accumulates per
 * import date via LoanImportService.
 */
class LoanDashboardService
{
    private const SEGMENT_CANON = [
        'CORPORATE BANKING'  => 'Corporate Banking',
        'COMMERCIAL BANKING' => 'Commercial Banking',
        'CONSUMER BANKING'   => 'Consumer Banking',
    ];

    private const SEGMENT_LABELS = [
        'Corporate Banking'  => 'Corporate',
        'Commercial Banking' => 'Commercial',
        'Consumer Banking'   => 'Consumer',
    ];

    private const SEGMENT_COLORS = [
        'Corporate Banking'  => '#005B82',
        'Commercial Banking' => '#008FC7',
        'Consumer Banking'   => '#10B981',
    ];

    // URL slugs used by the segment drill-down route (finance.loans.segment.show),
    // matching the segment cards on the loan dashboard.
    private const SEGMENT_SLUGS = [
        'corporate'  => 'Corporate Banking',
        'commercial' => 'Commercial Banking',
        'consumer'   => 'Consumer Banking',
    ];

    private const STATUS_ORDER = ['Performing', 'Watch', 'Substandard', 'Doubtful', 'Loss', 'Other'];

    private const STATUS_COLORS = [
        'Performing'  => '#10B981',
        'Watch'       => '#F59E0B',
        'Substandard' => '#F97316',
        'Doubtful'    => '#EF4444',
        'Loss'        => '#7F1D1D',
        'Other'       => '#94A3B8',
    ];

    // Mirrors the performing-loan whereRaw filter already used across
    // LoanMovementService / Email*MoversCommand — kept as a CASE expression
    // here (rather than a WHERE filter) so a single query yields both totals.
    private const PERFORMING_CASE = "(TRIM(COALESCE(loan_listings.loan_status, '')) = ''
        OR loan_listings.loan_status IN ('NORM', 'Normal', 'OAEM', 'SUBS', 'Watch'))";

    public function __construct(private LoanMovementService $loanMovementService)
    {
    }

    public function latestDate(): ?string
    {
        $date = DB::table('loan_listings')->max('as_at_date');

        return $date ? Carbon::parse((string) $date)->toDateString() : null;
    }

    /**
     * Resolves a URL slug (e.g. "corporate") to its canonical segment name,
     * short display label, and accent color — mirrors
     * FinanceSegmentController::resolveSegment() for deposits.
     */
    public static function resolveSegmentSlug(string $slug): ?array
    {
        $canon = self::SEGMENT_SLUGS[strtolower(trim($slug))] ?? null;

        if (!$canon) {
            return null;
        }

        return [
            'slug'  => strtolower(trim($slug)),
            'canon' => $canon,
            'label' => self::SEGMENT_LABELS[$canon],
            'color' => self::SEGMENT_COLORS[$canon],
        ];
    }

    /** All segment slugs in display order, for building segment card links. */
    public static function segmentSlugs(): array
    {
        return self::SEGMENT_SLUGS;
    }

    public function buildDashboardPayload(string $asOfDate): array
    {
        // This scans 15 months of loan_listings on every call — as that
        // table grows from daily imports, this gets slow enough to trip
        // nginx's gateway timeout on every single page load. Cache it.
        return Cache::remember(
            $this->dashboardCacheKey($asOfDate),
            now()->addMinutes(15),
            fn () => $this->buildDashboardPayloadUncached($asOfDate)
        );
    }

    /**
     * Forces a fresh rebuild of the dashboard payload for a date, replacing
     * whatever is cached. Called after an import/send from the loans pipeline
     * so the dashboard reflects the new data immediately instead of waiting
     * out the 15-minute cache TTL.
     */
    public function refreshDashboardPayload(string $asOfDate): array
    {
        Cache::forget($this->dashboardCacheKey($asOfDate));

        return $this->buildDashboardPayload($asOfDate);
    }

    private function dashboardCacheKey(string $asOfDate): string
    {
        return "loan_dashboard_payload:{$asOfDate}";
    }

    private function buildDashboardPayloadUncached(string $asOfDate): array
    {
        $historyStart = Carbon::parse($asOfDate)->subMonths(15)->startOfMonth()->toDateString();

        [$bankTotalSeries, $performingSeries, $segmentSeries, $statusSeries] =
            $this->fetchHistorySeries($historyStart, $asOfDate);

        $dateKeys = array_keys($bankTotalSeries);
        sort($dateKeys);

        if (empty($dateKeys)) {
            return $this->emptyPayload();
        }

        $dailyClosings   = $this->buildPeriodClosings($dateKeys, 'daily', 30);
        $weeklyClosings  = $this->buildPeriodClosings($dateKeys, 'weekly', 12);
        $monthlyClosings = $this->buildPeriodClosings($dateKeys, 'monthly', 12);

        $eoyBaseline = $this->resolveEoyBaselineClosing($dateKeys, $asOfDate);

        $dailyBreakdownClosings   = $this->prependBaselineClosing($dailyClosings, $eoyBaseline);
        $weeklyBreakdownClosings  = $this->prependBaselineClosing($weeklyClosings, $eoyBaseline);
        $monthlyBreakdownClosings = $this->prependBaselineClosing($monthlyClosings, $eoyBaseline);

        $dailyStart = $this->latestAvailableDateOnOrBefore($dateKeys, $asOfDate, true);

        // Reuse the existing two-date comparison for "as at today" figures —
        // avoids re-deriving segment/mover logic that build's already tested.
        $combined = $this->loanMovementService->buildCombined($dailyStart ?? $asOfDate, $asOfDate, 10);

        return [
            'asOfDate'     => $asOfDate,
            'summaryCards' => $this->buildSummaryCards($performingSeries, $statusSeries, $asOfDate, $combined),
            'mtdYtdPayload' => $this->buildMtdYtdPayload($segmentSeries, $asOfDate),
            'topMovers'    => $combined['movers'] ?? ['gainers' => [], 'losers' => []],
            'chartPayload' => [
                'overall' => [
                    'daily'   => $this->buildOverallMovementPayload($performingSeries, $dailyClosings),
                    'weekly'  => $this->buildOverallMovementPayload($performingSeries, $weeklyClosings),
                    'monthly' => $this->buildOverallMovementPayload($performingSeries, $monthlyClosings),
                ],
                'overallBreakdown' => [
                    'daily'   => $this->buildOverallBreakdownPayload($segmentSeries, $dailyBreakdownClosings),
                    'weekly'  => $this->buildOverallBreakdownPayload($segmentSeries, $weeklyBreakdownClosings),
                    'monthly' => $this->buildOverallBreakdownPayload($segmentSeries, $monthlyBreakdownClosings),
                ],
                'segments' => [
                    'daily'   => $this->buildSegmentMovementPayload($segmentSeries, $dailyClosings),
                    'weekly'  => $this->buildSegmentMovementPayload($segmentSeries, $weeklyClosings),
                    'monthly' => $this->buildSegmentMovementPayload($segmentSeries, $monthlyClosings),
                ],
                'segmentPie'    => $this->buildSegmentPiePayload($combined['segments'] ?? []),
                'currencyMixPie' => $this->buildCurrencyMixPiePayload($asOfDate),
                'statusPie'     => $this->buildStatusPiePayload($statusSeries, $asOfDate),
            ],
        ];
    }

    /**
     * Single grouped pass over loan_listings for the whole lookback window —
     * reshaped in PHP into four balance-maps instead of four round-trips.
     *
     * @return array{0: array<string,float>, 1: array<string,float>, 2: array<string,array<string,float>>, 3: array<string,array<string,float>>}
     */
    private function fetchHistorySeries(string $historyStart, string $asOfDate): array
    {
        $cifBiz = $this->loanMovementService->cifBusinessSubquery();

        $rows = DB::table('loan_listings')
            ->leftJoinSub($cifBiz, 'csm', fn($j) => $j->on('csm.cif', '=', 'loan_listings.cif'))
            ->selectRaw('loan_listings.as_at_date AS as_at_date')
            ->selectRaw($this->loanMovementService->segmentExpr() . ' AS business_segment')
            ->selectRaw('loan_listings.status_bucket AS status_bucket')
            ->selectRaw('CASE WHEN ' . self::PERFORMING_CASE . ' THEN 1 ELSE 0 END AS is_performing')
            ->selectRaw('SUM(loan_listings.loan_book_outstanding) AS balance')
            ->whereBetween('loan_listings.as_at_date', [$historyStart, $asOfDate])
            ->groupBy('loan_listings.as_at_date')
            ->groupBy(DB::raw($this->loanMovementService->segmentExpr()))
            ->groupBy('loan_listings.status_bucket')
            ->groupBy(DB::raw('CASE WHEN ' . self::PERFORMING_CASE . ' THEN 1 ELSE 0 END'))
            ->get();

        $bankTotalSeries  = [];
        $performingSeries = [];
        $segmentSeries    = [];
        $statusSeries     = [];

        foreach ($rows as $r) {
            $date        = (string) $r->as_at_date;
            $balance     = (float) $r->balance;
            $isPerforming = (int) $r->is_performing === 1;
            $bucket      = trim((string) ($r->status_bucket ?? '')) ?: 'Other';
            $segRaw      = strtoupper(trim((string) ($r->business_segment ?? 'UNMAPPED')));
            $segCanon    = self::SEGMENT_CANON[$segRaw] ?? null;

            $bankTotalSeries[$date] = ($bankTotalSeries[$date] ?? 0) + $balance;

            if ($isPerforming) {
                $performingSeries[$date] = ($performingSeries[$date] ?? 0) + $balance;

                if ($segCanon !== null) {
                    $segmentSeries[$segCanon][$date] = ($segmentSeries[$segCanon][$date] ?? 0) + $balance;
                }
            }

            $statusSeries[$bucket][$date] = ($statusSeries[$bucket][$date] ?? 0) + $balance;
        }

        ksort($bankTotalSeries);
        ksort($performingSeries);

        return [$bankTotalSeries, $performingSeries, $segmentSeries, $statusSeries];
    }

    private function buildPeriodClosings(array $dateKeys, string $mode, int $points): array
    {
        sort($dateKeys);

        if (empty($dateKeys)) {
            return [];
        }

        if ($mode === 'daily') {
            $closures = array_map(fn($d) => [
                'date'  => $d,
                'label' => Carbon::parse($d)->format('d M'),
            ], $dateKeys);

            return array_values(array_slice($closures, - ($points + 1)));
        }

        if ($mode === 'monthly') {
            $grouped = [];

            foreach ($dateKeys as $date) {
                $grouped[Carbon::parse($date)->format('Y-m')] = $date;
            }

            $closures = [];

            foreach ($grouped as $date) {
                $closures[] = [
                    'date'  => $date,
                    'label' => Carbon::parse($date)->format('M Y'),
                ];
            }

            return array_values(array_slice($closures, - ($points + 1)));
        }

        if ($mode === 'weekly') {
            $firstDate = Carbon::parse($dateKeys[0]);
            $lastDate  = Carbon::parse($dateKeys[count($dateKeys) - 1]);

            $friday = $firstDate->copy();
            $daysUntilFriday = (Carbon::FRIDAY - $friday->dayOfWeek + 7) % 7;
            $friday->addDays($daysUntilFriday);

            $closures = [];
            $seen     = [];

            while ($friday->lte($lastDate)) {
                $candidate = $this->latestAvailableDateOnOrBefore($dateKeys, $friday->toDateString());

                if ($candidate !== null && !isset($seen[$candidate])) {
                    $seen[$candidate] = true;
                    $dt = Carbon::parse($candidate);
                    $closures[] = [
                        'date'  => $candidate,
                        'label' => $dt->format('d M') . ($dt->dayOfWeek !== Carbon::FRIDAY ? '*' : ''),
                    ];
                }

                $friday->addWeek();
            }

            return array_values(array_slice($closures, - ($points + 1)));
        }

        return [];
    }

    private function resolveEoyBaselineClosing(array $dateKeys, string $asOfDate): ?array
    {
        $targetDate   = Carbon::parse($asOfDate)->startOfYear()->subDay()->toDateString();
        $baselineDate = $this->latestAvailableDateOnOrBefore($dateKeys, $targetDate);

        if (!$baselineDate) {
            return null;
        }

        return [
            'date'        => $baselineDate,
            'label'       => 'EOY ' . Carbon::parse($baselineDate)->format('Y'),
            'is_baseline' => true,
        ];
    }

    private function prependBaselineClosing(array $periodClosings, ?array $baseline): array
    {
        if (!$baseline) {
            return $periodClosings;
        }

        $periodClosings = array_values(array_filter(
            $periodClosings,
            fn($point) => (string) $point['date'] !== (string) $baseline['date']
        ));

        array_unshift($periodClosings, $baseline);

        return $periodClosings;
    }

    private function buildOverallMovementPayload(array $balances, array $periodClosings): array
    {
        if (count($periodClosings) < 2) {
            return ['labels' => [], 'data' => [], 'periods' => [], 'closingBalances' => []];
        }

        $labels = [];
        $data = [];
        $periods = [];
        $closingBalances = [];

        for ($i = 1; $i < count($periodClosings); $i++) {
            $from = $periodClosings[$i - 1]['date'];
            $to   = $periodClosings[$i]['date'];

            $labels[] = $periodClosings[$i]['label'];
            $data[] = round((float) ($balances[$to] ?? 0) - (float) ($balances[$from] ?? 0), 2);
            $closingBalances[] = round((float) ($balances[$to] ?? 0), 2);
            $periods[] = ['from' => $from, 'to' => $to];
        }

        return compact('labels', 'data', 'periods', 'closingBalances');
    }

    private function buildOverallBreakdownPayload(array $segmentSeries, array $periodClosings): array
    {
        if (empty($periodClosings)) {
            return ['labels' => [], 'datasets' => [], 'periods' => []];
        }

        $labels = [];
        $periods = [];

        foreach ($periodClosings as $point) {
            $labels[] = $point['label'];
            $periods[] = [
                'date'        => $point['date'],
                'is_baseline' => !empty($point['is_baseline']),
            ];
        }

        $datasets = [];

        foreach (self::SEGMENT_LABELS as $canon => $label) {
            $series = $segmentSeries[$canon] ?? [];
            $dates  = array_keys($series);
            sort($dates);

            $values = [];
            $colors = [];

            foreach ($periodClosings as $point) {
                $effective = $this->latestAvailableDateOnOrBefore($dates, $point['date']);

                $values[] = $effective ? round((float) ($series[$effective] ?? 0), 2) : 0;
                $colors[] = !empty($point['is_baseline']) ? '#94A3B8' : self::SEGMENT_COLORS[$canon];
            }

            $datasets[] = [
                'label'  => $label,
                'data'   => $values,
                'color'  => self::SEGMENT_COLORS[$canon],
                'colors' => $colors,
            ];
        }

        return compact('labels', 'datasets', 'periods');
    }

    private function buildSegmentMovementPayload(array $segmentSeries, array $periodClosings): array
    {
        if (count($periodClosings) < 2) {
            return ['labels' => [], 'datasets' => [], 'periods' => []];
        }

        $labels = [];
        $periods = [];

        for ($i = 1; $i < count($periodClosings); $i++) {
            $labels[] = $periodClosings[$i]['label'];
            $periods[] = ['from' => $periodClosings[$i - 1]['date'], 'to' => $periodClosings[$i]['date']];
        }

        $datasets = [];

        foreach (self::SEGMENT_LABELS as $canon => $label) {
            $series = [];

            for ($i = 1; $i < count($periodClosings); $i++) {
                $from = $periodClosings[$i - 1]['date'];
                $to   = $periodClosings[$i]['date'];

                $series[] = round(
                    (float) ($segmentSeries[$canon][$to] ?? 0) - (float) ($segmentSeries[$canon][$from] ?? 0),
                    2
                );
            }

            $datasets[] = [
                'label' => $label,
                'data'  => $series,
                'color' => self::SEGMENT_COLORS[$canon],
            ];
        }

        return compact('labels', 'datasets', 'periods');
    }

    private function buildSegmentPiePayload(array $combinedSegments): array
    {
        $labels = [];
        $data = [];
        $colors = [];

        foreach (self::SEGMENT_LABELS as $canon => $label) {
            $seg = collect($combinedSegments)->firstWhere('name', $canon);
            $balance = $seg ? (float) ($seg['endBalance'] ?? 0) : 0.0;

            if ($balance > 0) {
                $labels[] = $label;
                $data[] = round($balance, 2);
                $colors[] = self::SEGMENT_COLORS[$canon];
            }
        }

        return compact('labels', 'data', 'colors');
    }

    private function buildCurrencyMixPiePayload(string $asOfDate): array
    {
        $rows = DB::table('loan_listings')
            ->where('as_at_date', $asOfDate)
            ->selectRaw('currency_type, SUM(loan_book_outstanding) AS balance')
            ->groupBy('currency_type')
            ->get();

        $totals = ['LCY' => 0.0, 'FCY' => 0.0];

        foreach ($rows as $r) {
            $ct = strtoupper(trim((string) ($r->currency_type ?? '')));

            if (isset($totals[$ct])) {
                $totals[$ct] += (float) $r->balance;
            }
        }

        $colorMap = ['LCY' => '#005B82', 'FCY' => '#10B981'];
        $labels = [];
        $data = [];
        $colors = [];

        foreach ($totals as $code => $value) {
            if ($value > 0) {
                $labels[] = $code;
                $data[] = round($value, 2);
                $colors[] = $colorMap[$code];
            }
        }

        return compact('labels', 'data', 'colors');
    }

    private function buildStatusPiePayload(array $statusSeries, string $asOfDate): array
    {
        $labels = [];
        $data = [];
        $colors = [];

        foreach (self::STATUS_ORDER as $bucket) {
            $series = $statusSeries[$bucket] ?? [];
            $dates  = array_keys($series);
            sort($dates);

            $effective = $this->latestAvailableDateOnOrBefore($dates, $asOfDate);
            $balance   = $effective ? round((float) ($series[$effective] ?? 0), 2) : 0;

            if ($balance > 0) {
                $labels[] = $bucket;
                $data[] = $balance;
                $colors[] = self::STATUS_COLORS[$bucket];
            }
        }

        return compact('labels', 'data', 'colors');
    }

    private function buildMtdYtdPayload(array $segmentSeries, string $asOfDate): array
    {
        $mtdStartDate = Carbon::parse($asOfDate)->startOfMonth()->subDay()->toDateString();
        $ytdStartDate = Carbon::parse($asOfDate)->startOfYear()->subDay()->toDateString();

        $labels = [];
        $mtd = [];
        $ytd = [];
        $colors = [];

        foreach (self::SEGMENT_LABELS as $canon => $label) {
            $series = $segmentSeries[$canon] ?? [];
            $dates  = array_keys($series);
            sort($dates);

            $effectiveDate = $this->latestAvailableDateOnOrBefore($dates, $asOfDate);
            $currentVal    = $effectiveDate ? (float) ($series[$effectiveDate] ?? 0) : 0.0;

            $mtdStart = $this->latestAvailableDateOnOrBefore($dates, $mtdStartDate);
            $ytdStart = $this->latestAvailableDateOnOrBefore($dates, $ytdStartDate);

            $labels[] = $label;
            $mtd[] = $mtdStart ? round($currentVal - (float) ($series[$mtdStart] ?? 0), 2) : 0;
            $ytd[] = $ytdStart ? round($currentVal - (float) ($series[$ytdStart] ?? 0), 2) : 0;
            $colors[] = self::SEGMENT_COLORS[$canon];
        }

        return compact('labels', 'mtd', 'ytd', 'colors');
    }

    private function buildSummaryCards(array $performingSeries, array $statusSeries, string $asOfDate, array $combined): array
    {
        $allDates = array_keys($performingSeries);
        sort($allDates);

        $effectiveDate = $this->latestAvailableDateOnOrBefore($allDates, $asOfDate) ?? $asOfDate;

        $dailyStart = $this->latestAvailableDateOnOrBefore($allDates, $effectiveDate, true);
        $mtdStart = $this->latestAvailableDateOnOrBefore(
            $allDates,
            Carbon::parse($asOfDate)->startOfMonth()->subDay()->toDateString()
        );
        $ytdStart = $this->latestAvailableDateOnOrBefore(
            $allDates,
            Carbon::parse($asOfDate)->startOfYear()->subDay()->toDateString()
        );

        $current = (float) ($performingSeries[$effectiveDate] ?? 0);

        $performingTotal = (float) ($combined['totals']['endBalance'] ?? $current);
        $bankTotal       = (float) ($combined['totals']['bankTotalEnd'] ?? 0);

        $nplPct = null;
        if ($bankTotal > 0) {
            $nonPerforming = 0.0;
            foreach (self::STATUS_ORDER as $bucket) {
                if ($bucket === 'Performing') {
                    continue;
                }
                $series = $statusSeries[$bucket] ?? [];
                $dates  = array_keys($series);
                sort($dates);
                $effective = $this->latestAvailableDateOnOrBefore($dates, $asOfDate);
                $nonPerforming += $effective ? (float) ($series[$effective] ?? 0) : 0;
            }
            $nplPct = round(($nonPerforming / $bankTotal) * 100, 2);
        }

        return [
            $this->buildMovementCard('Daily Movement', $current, (float) ($performingSeries[$dailyStart] ?? 0), $dailyStart, $effectiveDate, '#0082BB'),
            $this->buildMovementCard('MTD Movement', $current, (float) ($performingSeries[$mtdStart] ?? 0), $mtdStart, $effectiveDate, '#10B981'),
            $this->buildMovementCard('YTD Movement', $current, (float) ($performingSeries[$ytdStart] ?? 0), $ytdStart, $effectiveDate, '#F59E0B'),
            [
                'label' => 'Performing Loan Book',
                'value' => $this->formatMoneyShort($performingTotal),
                'raw' => round($performingTotal, 2),
                'direction' => 'flat',
                'change_pct' => null,
                'range' => 'Closing balance · ' . Carbon::parse($effectiveDate)->format('d M Y'),
                'accent' => '#005B82',
                'badge' => 'PERFORMING',
            ],
            [
                'label' => 'Total Loan Book (Bank-Wide)',
                'value' => $this->formatMoneyShort($bankTotal),
                'raw' => round($bankTotal, 2),
                'direction' => 'flat',
                'change_pct' => null,
                'range' => 'All statuses · ' . Carbon::parse($effectiveDate)->format('d M Y'),
                'accent' => '#0082BB',
                'badge' => 'TOTAL',
            ],
            [
                'label' => 'Asset Quality (NPL)',
                'value' => $nplPct !== null ? number_format($nplPct, 2) . '%' : 'Pending',
                'raw' => $nplPct,
                'direction' => 'flat',
                'change_pct' => null,
                'range' => $nplPct !== null
                    ? 'Watch + Substandard + Doubtful + Loss · ' . Carbon::parse($effectiveDate)->format('d M Y')
                    : 'Insufficient data',
                'accent' => '#EF4444',
                'is_placeholder' => $nplPct === null,
            ],
        ];
    }

    private function buildMovementCard(
        string $label,
        float $current,
        float $previous,
        ?string $fromDate,
        string $toDate,
        string $accent
    ): array {
        // No available snapshot on/before the comparison window yet (e.g. YTD
        // before loan_listings has any December-of-last-year history) — don't
        // fall back to comparing against 0, which would render the whole
        // current balance as if it were the period's movement.
        if ($fromDate === null) {
            return [
                'label' => $label,
                'value' => 'Pending',
                'raw' => null,
                'direction' => 'flat',
                'change_pct' => null,
                'range' => 'Insufficient history before ' . Carbon::parse($toDate)->format('d M Y'),
                'accent' => $accent,
                'is_placeholder' => true,
            ];
        }

        $movement = round($current - $previous, 2);
        $changePct = abs($previous) > 0.00001
            ? round(($movement / abs($previous)) * 100, 2)
            : null;

        return [
            'label' => $label,
            'value' => $this->formatMoneyShort($movement),
            'raw' => $movement,
            'direction' => $movement >= 0 ? 'up' : 'down',
            'change_pct' => $changePct,
            'range' => Carbon::parse($fromDate)->format('d M Y') . ' → ' . Carbon::parse($toDate)->format('d M Y'),
            'accent' => $accent,
        ];
    }

    private function latestAvailableDateOnOrBefore(array $dateKeys, string $targetDate, bool $strictlyBefore = false): ?string
    {
        sort($dateKeys);

        $candidate = null;
        foreach ($dateKeys as $date) {
            if ($strictlyBefore ? $date < $targetDate : $date <= $targetDate) {
                $candidate = $date;
            } else {
                break;
            }
        }

        return $candidate;
    }

    private function formatMoneyShort(float $value): string
    {
        $prefix = $value < 0 ? '-KES ' : 'KES ';
        $abs = abs($value);

        if ($abs >= 1000000000) {
            return $prefix . number_format($abs / 1000000000, 2) . 'B';
        }

        if ($abs >= 1000000) {
            return $prefix . number_format($abs / 1000000, 2) . 'M';
        }

        if ($abs >= 1000) {
            return $prefix . number_format($abs / 1000, 2) . 'K';
        }

        return $prefix . number_format($abs, 2);
    }

    public function emptyPayload(): array
    {
        return [
            'asOfDate' => null,
            'summaryCards' => [],
            'mtdYtdPayload' => ['labels' => [], 'mtd' => [], 'ytd' => [], 'colors' => []],
            'topMovers' => ['gainers' => [], 'losers' => []],
            'chartPayload' => [
                'overall' => ['daily' => [], 'weekly' => [], 'monthly' => []],
                'overallBreakdown' => ['daily' => [], 'weekly' => [], 'monthly' => []],
                'segments' => ['daily' => [], 'weekly' => [], 'monthly' => []],
                'segmentPie' => ['labels' => [], 'data' => [], 'colors' => []],
                'currencyMixPie' => ['labels' => [], 'data' => [], 'colors' => []],
                'statusPie' => ['labels' => [], 'data' => [], 'colors' => []],
            ],
        ];
    }
}
