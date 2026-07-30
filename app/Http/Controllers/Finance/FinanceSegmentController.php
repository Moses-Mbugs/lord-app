<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\SubSegmentMapping;
use App\Models\Finance\SubSegmentMover;
use App\Services\Reports\SubSegmentMoversService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceSegmentController extends Controller
{
    private const SEGMENTS = [
        'corporate' => [
            'slug' => 'corporate',
            'code' => 'CB',
            'label' => 'Corporate',
            'business_segment_name' => 'Corporate Banking',
            'color' => '#005B82',
            'aliases' => ['corporate', 'corporate banking'],
        ],
        'commercial' => [
            'slug' => 'commercial',
            'code' => 'CM',
            'label' => 'Commercial',
            'business_segment_name' => 'Commercial Banking',
            'color' => '#008FC7',
            'aliases' => ['commercial', 'commercial banking'],
        ],
        'consumer' => [
            'slug' => 'consumer',
            'code' => 'CS',
            'label' => 'Consumer',
            'business_segment_name' => 'Consumer Banking',
            'color' => '#10B981',
            'aliases' => ['consumer', 'consumer banking', 'retail', 'personal'],
        ],
        'others' => [
            'slug' => 'others',
            'code' => 'OT',
            'label' => 'Others',
            'business_segment_name' => 'Others',
            'color' => '#BED600',
            'aliases' => ['others', 'other'],
        ],
    ];

    private const CHART_COLORS = [
        '#005B82',
        '#0082BB',
        '#009FD1',
        '#10B981',
        '#34D399',
        '#BED600',
        '#7CB342',
        '#5BC0BE',
        '#94A3B8',
    ];

    private const SUMMARY_SCOPE_SEGMENT = 'SEGMENT';

    public function show(string $segment): View
    {
        $config = $this->resolveSegment($segment);
        abort_unless($config !== null, 404);

        $misCodes = $this->resolveMisCodes($config);
        $asOfDate = $this->latestSubSegmentDate($misCodes) ?: $this->latestOverallDate();

        if (!$asOfDate || empty($misCodes)) {
            return view('finance.sub_segements.show', $this->emptyPayload($config));
        }

        return view('finance.sub_segements.show', $this->buildPayload($config, $misCodes, $asOfDate));
    }

    private function resolveSegment(string $segment): ?array
    {
        $key = strtolower(trim($segment));

        $aliases = [
            'cb' => 'corporate',
            'cm' => 'commercial',
            'cs' => 'consumer',
            'ot' => 'others',
        ];

        $resolved = $aliases[$key] ?? $key;

        return self::SEGMENTS[$resolved] ?? null;
    }

    private function buildPayload(array $config, array $misCodes, string $asOfDate): array
    {
        $historyStart = Carbon::parse($asOfDate)->subMonths(15)->startOfMonth()->toDateString();

        $mappingRows = $this->fetchMappings($misCodes);
        $allRows = $this->fetchSubSegmentRows($misCodes, $historyStart, $asOfDate);

        $seriesMap = $this->subSegmentSeriesToBalanceMap($allRows, $mappingRows);
        $dateKeys = $this->collectSeriesDates($seriesMap);
        $totalSeries = $this->buildTotalSeries($seriesMap);

        $effectiveDate = $this->latestAvailableDateOnOrBefore(array_keys($totalSeries), $asOfDate) ?? $asOfDate;
        $segmentTotal = (float) ($totalSeries[$effectiveDate] ?? 0);
        $overallTotal = $this->fetchOverallTotalAtDate($effectiveDate);
        $sharePct = $overallTotal > 0 ? round(($segmentTotal / $overallTotal) * 100, 1) : 0;

        $mixSummary = $this->fetchSegmentMixSummaryOnOrBefore($effectiveDate, $config['code']);

        $subSegmentRows = $this->buildCurrentRows($seriesMap, $effectiveDate, $segmentTotal);
        $dailyClosings = $this->buildPeriodClosings($dateKeys, 'daily', 30);
        $weeklyClosings = $this->buildPeriodClosings($dateKeys, 'weekly', 12);
        $monthlyClosings = $this->buildPeriodClosings($dateKeys, 'monthly', 12);

        return [
            'asOfDate' => $effectiveDate,
            'segment' => [
                'slug' => $config['slug'],
                'code' => $config['code'],
                'label' => $config['label'],
                'business_segment_name' => $config['business_segment_name'],
                'color' => $config['color'],
                'total_deposit' => $segmentTotal,
                'overall_total' => $overallTotal,
                'share_pct' => $sharePct,
            ],
            'summaryCards' => $this->buildSummaryCards(
                $totalSeries,
                $effectiveDate,
                $config['color'],
                $mixSummary,
                $config
            ),
            'subSegmentCards' => array_slice($subSegmentRows, 0, 12),
            'tableRows' => $subSegmentRows,
            'chartPayload' => [
                'deposits' => [
                    'daily' => $this->buildDepositBreakdownPayload($seriesMap, $subSegmentRows, $dailyClosings),
                    'weekly' => $this->buildDepositBreakdownPayload($seriesMap, $subSegmentRows, $weeklyClosings),
                    'monthly' => $this->buildDepositBreakdownPayload($seriesMap, $subSegmentRows, $monthlyClosings),
                ],
            ],
        ];
    }

    private function emptyPayload(array $config): array
    {
        return [
            'asOfDate' => null,
            'segment' => [
                'slug' => $config['slug'],
                'code' => $config['code'],
                'label' => $config['label'],
                'business_segment_name' => $config['business_segment_name'],
                'color' => $config['color'],
                'total_deposit' => 0,
                'overall_total' => 0,
                'share_pct' => 0,
            ],
            'summaryCards' => [],
            'subSegmentCards' => [],
            'tableRows' => [],
            'chartPayload' => [
                'deposits' => [
                    'daily' => ['labels' => [], 'datasets' => [], 'periods' => []],
                    'weekly' => ['labels' => [], 'datasets' => [], 'periods' => []],
                    'monthly' => ['labels' => [], 'datasets' => [], 'periods' => []],
                ],
            ],
        ];
    }

    private function resolveMisCodes(array $config): array
    {
        return SubSegmentMapping::query()
            ->whereRaw('LOWER(TRIM(COALESCE(business, ""))) = ?', [
                strtolower($config['business_segment_name'])
            ])
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->pluck('mis_code')
            ->filter()
            ->map(fn($code) => strtoupper(trim((string) $code)))
            ->unique()
            ->values()
            ->all();
    }
    private function latestSubSegmentDate(array $misCodes): ?string
    {
        if (empty($misCodes)) {
            return null;
        }

        $date = SubSegmentMover::query()
            ->whereIn('mis_code', $misCodes)
            ->max('end_date');

        return $date ? Carbon::parse($date)->toDateString() : null;
    }

    private function latestOverallDate(): ?string
    {
        $date = DB::table('segment_movers')->max('end_date');

        return $date ? Carbon::parse($date)->toDateString() : null;
    }

    private function fetchSegmentMixSummaryOnOrBefore(string $asOfDate, string $segmentCode): array
    {
        $row = DB::table('finance_daily_mix_summaries')
            ->whereDate('balance_date', '<=', $asOfDate)
            ->where('summary_scope', self::SUMMARY_SCOPE_SEGMENT)
            ->where('segment_code', strtoupper($segmentCode))
            ->orderByDesc('balance_date')
            ->first();

        if (!$row) {
            return [
                'balance_date' => null,
                'lcy_amount' => 0,
                'fcy_amount' => 0,
                'lcy_pct' => 0,
                'fcy_pct' => 0,
                'total_positive_lcy_balance' => 0,
            ];
        }

        return [
            'balance_date' => $row->balance_date ? Carbon::parse($row->balance_date)->toDateString() : null,
            'lcy_amount' => round((float) ($row->lcy_amount ?? 0), 2),
            'fcy_amount' => round((float) ($row->fcy_amount ?? 0), 2),
            'lcy_pct' => round((float) ($row->lcy_pct ?? 0), 2),
            'fcy_pct' => round((float) ($row->fcy_pct ?? 0), 2),
            'total_positive_lcy_balance' => round((float) ($row->total_positive_lcy_balance ?? 0), 2),
        ];
    }

    private function fetchMappings(array $misCodes)
    {
        return SubSegmentMapping::query()
            ->whereIn('mis_code', $misCodes)
            ->orderBy('business_segment_name')
            ->orderBy('mis_code')
            ->get([
                'mis_code',
                'business_segment_name',
            ]);
    }

    private function fetchSubSegmentRows(array $misCodes, string $startDate, string $endDate)
    {
        return SubSegmentMover::query()
            ->whereIn('mis_code', $misCodes)
            ->whereBetween('end_date', [$startDate, $endDate])
            ->orderBy('end_date')
            ->orderBy('mis_code')
            ->get([
                'mis_code',
                'start_date',
                'end_date',
                'start_balance',
                'end_balance',
                'movement',
                'cif_count',
            ]);
    }

    private function fetchOverallTotalAtDate(string $date): float
    {
        return (float) DB::table('segment_movers')
            ->whereIn('segment_code', ['CB', 'CM', 'CS', 'OT'])
            ->whereDate('end_date', $date)
            ->sum('end_balance');
    }

    private function subSegmentSeriesToBalanceMap($rows, $mappingRows): array
    {
        $map = [];
        $labelByCode = [];

        foreach ($mappingRows as $mapping) {
            $code = strtoupper(trim((string) $mapping->mis_code));
            if ($code === '') {
                continue;
            }

            $label = $this->normalizeBusinessSegmentLabel($mapping->business_segment_name, $code);
            $labelByCode[$code] = $label;

            $groupKey = $this->makeGroupKey($label);

            if (!isset($map[$groupKey])) {
                $map[$groupKey] = [
                    'group_key' => $groupKey,
                    'mis_code' => $label,
                    'label' => $label,
                    'mis_codes' => [],
                    'balances' => [],
                    'cif_counts' => [],
                ];
            }

            $map[$groupKey]['mis_codes'][$code] = $code;
        }

        foreach ($rows as $row) {
            $code = strtoupper(trim((string) ($row->mis_code ?: 'UNMAPPED')));
            $label = $labelByCode[$code] ?? $this->normalizeBusinessSegmentLabel(null, $code);
            $groupKey = $this->makeGroupKey($label);

            if (!isset($map[$groupKey])) {
                $map[$groupKey] = [
                    'group_key' => $groupKey,
                    'mis_code' => $label,
                    'label' => $label,
                    'mis_codes' => [],
                    'balances' => [],
                    'cif_counts' => [],
                ];
            }

            $map[$groupKey]['mis_codes'][$code] = $code;

            $date = Carbon::parse($row->end_date)->toDateString();

            $map[$groupKey]['balances'][$date] = round(
                (float) ($map[$groupKey]['balances'][$date] ?? 0) + (float) $row->end_balance,
                2
            );

            $map[$groupKey]['cif_counts'][$date] = (int) ($map[$groupKey]['cif_counts'][$date] ?? 0) + (int) $row->cif_count;
        }

        uasort($map, fn($a, $b) => strcmp($a['label'], $b['label']));

        return $map;
    }

    private function collectSeriesDates(array $seriesMap): array
    {
        $dates = [];

        foreach ($seriesMap as $series) {
            $dates = array_merge($dates, array_keys($series['balances']));
        }

        $dates = array_values(array_unique($dates));
        sort($dates);

        return $dates;
    }

    private function buildTotalSeries(array $seriesMap): array
    {
        $dates = $this->collectSeriesDates($seriesMap);
        $totals = [];

        foreach ($dates as $date) {
            $sum = 0;

            foreach ($seriesMap as $series) {
                $effective = $this->latestAvailableDateOnOrBefore(array_keys($series['balances']), $date);
                $sum += $effective ? (float) ($series['balances'][$effective] ?? 0) : 0;
            }

            $totals[$date] = round($sum, 2);
        }

        ksort($totals);

        return $totals;
    }

    private function buildCurrentRows(array $seriesMap, string $effectiveDate, float $segmentTotal): array
    {
        $mtdCutoff = Carbon::parse($effectiveDate)->startOfMonth()->subDay()->toDateString();
        $ytdCutoff = Carbon::parse($effectiveDate)->startOfYear()->subDay()->toDateString();

        $rows = [];

        foreach ($seriesMap as $groupKey => $series) {
            $dates = array_keys($series['balances']);
            sort($dates);

            $currentDate = $this->latestAvailableDateOnOrBefore($dates, $effectiveDate);
            $dailyDate = $this->latestAvailableDateOnOrBefore($dates, $effectiveDate, true);
            $mtdDate = $this->latestAvailableDateOnOrBefore($dates, $mtdCutoff);
            $ytdDate = $this->latestAvailableDateOnOrBefore($dates, $ytdCutoff);

            $currentBalance = $currentDate ? (float) ($series['balances'][$currentDate] ?? 0) : 0.0;
            $dailyBalance = $dailyDate ? (float) ($series['balances'][$dailyDate] ?? 0) : 0.0;
            $mtdBalance = $mtdDate ? (float) ($series['balances'][$mtdDate] ?? 0) : 0.0;
            $ytdBalance = $ytdDate ? (float) ($series['balances'][$ytdDate] ?? 0) : 0.0;
            $cifCount = $currentDate ? (int) ($series['cif_counts'][$currentDate] ?? 0) : 0;

            $rows[] = [
                'group_key' => $groupKey,
                'mis_code' => $series['mis_code'],
                'label' => $series['label'],
                'mis_codes' => array_values($series['mis_codes']),
                'closing_balance' => round($currentBalance, 2),
                'daily_movement' => round($currentBalance - $dailyBalance, 2),
                'mtd_movement' => round($currentBalance - $mtdBalance, 2),
                'ytd_movement' => round($currentBalance - $ytdBalance, 2),
                'cif_count' => $cifCount,
                'share_pct' => $segmentTotal > 0 ? round(($currentBalance / $segmentTotal) * 100, 1) : 0,
            ];
        }

        usort($rows, fn($a, $b) => $b['closing_balance'] <=> $a['closing_balance']);

        return $rows;
    }

    private function buildSummaryCards(
        array $totalSeries,
        string $effectiveDate,
        string $accent,
        array $mixSummary,
        array $config
    ): array {
        $allDates = array_keys($totalSeries);
        sort($allDates);

        $current = (float) ($totalSeries[$effectiveDate] ?? 0);

        $dailyStart = $this->latestAvailableDateOnOrBefore($allDates, $effectiveDate, true);
        $mtdStart = $this->latestAvailableDateOnOrBefore(
            $allDates,
            Carbon::parse($effectiveDate)->startOfMonth()->subDay()->toDateString()
        );
        return [
            $this->movementCard(
                'Daily Movement',
                $current,
                $dailyStart ? (float) ($totalSeries[$dailyStart] ?? 0) : 0,
                $dailyStart,
                $effectiveDate,
                $accent
            ),
            $this->movementCard(
                'MTD',
                $current,
                $mtdStart ? (float) ($totalSeries[$mtdStart] ?? 0) : 0,
                $mtdStart,
                $effectiveDate,
                '#10B981'
            ),
            $this->buildSegmentLevelYtdCard($config, $effectiveDate, '#008FC7'),
            $this->currencyMixCard($mixSummary),
            $this->depositMixCard($mixSummary),
        ];
    }

    private function movementCard(
        string $label,
        float $current,
        float $previous,
        ?string $fromDate,
        string $toDate,
        string $accent
    ): array {
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
            'range' => $fromDate
                ? Carbon::parse($fromDate)->format('d M Y') . ' → ' . Carbon::parse($toDate)->format('d M Y')
                : 'Insufficient history',
            'accent' => $accent,
        ];
    }

    private function currencyMixCard(array $mixSummary): array
    {
        if (!$mixSummary['balance_date']) {
            return [
                'label' => 'Currency Mix',
                'value' => 'Pending',
                'raw' => null,
                'direction' => 'flat',
                'change_pct' => null,
                'range' => 'Segment daily mix summary not built',
                'accent' => '#005B82',
                'is_placeholder' => true,
            ];
        }

        $lcyPct = round((float) ($mixSummary['lcy_pct'] ?? 0), 1);
        $fcyPct = round((float) ($mixSummary['fcy_pct'] ?? 0), 1);

        return [
            'label' => 'Currency Mix',
            'value_lines' => [
                'LCY ' . number_format($lcyPct, 1) . '%',
                'FCY ' . number_format($fcyPct, 1) . '%',
            ],
            'raw' => round((float) ($mixSummary['total_positive_lcy_balance'] ?? 0), 2),
            'direction' => 'flat',
            'change_pct' => null,
            'range' => 'Positive LCY balances · ' . Carbon::parse($mixSummary['balance_date'])->format('d M Y'),
            'accent' => '#005B82',
        ];
    }

    private function depositMixCard(array $mixSummary): array
    {
        if (!$mixSummary['balance_date']) {
            return [
                'label' => 'Deposit Mix',
                'value' => 'Pending',
                'raw' => null,
                'direction' => 'flat',
                'change_pct' => null,
                'range' => 'Segment daily mix summary not built',
                'accent' => '#10B981',
                'is_placeholder' => true,
            ];
        }

        return [
            'label' => 'Deposit Mix',
            'value_lines' => [
                'LCY ' . $this->formatMoneyShort((float) ($mixSummary['lcy_amount'] ?? 0)),
                'FCY ' . $this->formatMoneyShort((float) ($mixSummary['fcy_amount'] ?? 0)),
            ],
            'raw' => round((float) ($mixSummary['lcy_amount'] ?? 0) + (float) ($mixSummary['fcy_amount'] ?? 0), 2),
            'direction' => 'flat',
            'change_pct' => null,
            'range' => 'LCY + FCY deposits · ' . Carbon::parse($mixSummary['balance_date'])->format('d M Y'),
            'accent' => '#10B981',
        ];
    }

    private function buildDepositBreakdownPayload(array $seriesMap, array $currentRows, array $periodClosings): array
    {
        if (empty($periodClosings)) {
            return ['labels' => [], 'datasets' => [], 'periods' => []];
        }

        $labels = [];
        $periods = [];

        foreach ($periodClosings as $point) {
            $labels[] = $point['label'];
            $periods[] = ['date' => $point['date']];
        }

        $topRows = array_slice($currentRows, 0, 6);
        $topKeys = array_map(fn($row) => $row['group_key'], $topRows);

        $datasets = [];

        foreach ($topRows as $index => $row) {
            $series = $seriesMap[$row['group_key']] ?? ['balances' => []];
            $dates = array_keys($series['balances']);
            sort($dates);

            $values = [];
            foreach ($periodClosings as $point) {
                $effective = $this->latestAvailableDateOnOrBefore($dates, $point['date']);
                $values[] = $effective ? round((float) ($series['balances'][$effective] ?? 0), 2) : 0;
            }

            $datasets[] = [
                'label' => $row['label'],
                'data' => $values,
                'color' => self::CHART_COLORS[$index % count(self::CHART_COLORS)],
            ];
        }

        $otherValues = [];

        foreach ($periodClosings as $point) {
            $total = 0;
            $topSum = 0;

            foreach ($seriesMap as $groupKey => $series) {
                $dates = array_keys($series['balances']);
                sort($dates);

                $effective = $this->latestAvailableDateOnOrBefore($dates, $point['date']);
                $balance = $effective ? (float) ($series['balances'][$effective] ?? 0) : 0;

                $total += $balance;
                if (in_array($groupKey, $topKeys, true)) {
                    $topSum += $balance;
                }
            }

            $otherValues[] = round(max(0, $total - $topSum), 2);
        }

        if (array_sum($otherValues) > 0) {
            $datasets[] = [
                'label' => 'Other Sub-segments',
                'data' => $otherValues,
                'color' => '#CBD5E1',
            ];
        }

        return compact('labels', 'datasets', 'periods');
    }

    private function buildPeriodClosings(array $dateKeys, string $mode, int $points): array
    {
        sort($dateKeys);

        if (empty($dateKeys)) {
            return [];
        }

        if ($mode === 'daily') {
            $closures = array_map(fn($date) => [
                'date' => $date,
                'label' => Carbon::parse($date)->format('d M'),
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
                    'date' => $date,
                    'label' => Carbon::parse($date)->format('M Y'),
                ];
            }

            return array_values(array_slice($closures, - ($points + 1)));
        }

        if ($mode === 'weekly') {
            $firstDate = Carbon::parse($dateKeys[0]);
            $lastDate = Carbon::parse($dateKeys[count($dateKeys) - 1]);

            $friday = $firstDate->copy();
            $daysUntilFriday = (Carbon::FRIDAY - $friday->dayOfWeek + 7) % 7;
            $friday->addDays($daysUntilFriday);

            $closures = [];
            $seen = [];

            while ($friday->lte($lastDate)) {
                $candidate = $this->latestAvailableDateOnOrBefore($dateKeys, $friday->toDateString());

                if ($candidate !== null && !isset($seen[$candidate])) {
                    $seen[$candidate] = true;
                    $dt = Carbon::parse($candidate);
                    $closures[] = [
                        'date' => $candidate,
                        'label' => $dt->format('d M') . ($dt->dayOfWeek !== Carbon::FRIDAY ? '*' : ''),
                    ];
                }

                $friday->addWeek();
            }

            return array_values(array_slice($closures, - ($points + 1)));
        }

        return [];
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

    private function normalizeBusinessSegmentLabel(?string $name, string $fallbackCode): string
    {
        $label = trim((string) $name);

        if ($label === '') {
            return strtoupper($fallbackCode);
        }

        $label = preg_replace('/\s+/', ' ', $label) ?: $label;
        $lower = strtolower($label);

        return match ($lower) {
            'public sector', 'ps' => 'Public Sector',
            'local corporates', 'local corporate', 'lc' => 'Local Corporates',
            'regional corporates', 'regional corporate', 'rc' => 'Regional Corporates',
            'multinational corporates', 'multinational corporate', 'mc' => 'Multinational Corporates',
            'financial institutions', 'fi' => 'Financial Institutions',
            'international organisations', 'international organizations', 'io' => 'International Organisations',
            default => ucwords($lower),
        };
    }

    private function makeGroupKey(string $label): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]+/i', '_', trim($label)) ?: 'UNMAPPED');
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

    public function cifDrivers(
        Request $request,
        string $segment,
        SubSegmentMoversService $subSegmentMoversService
    ): JsonResponse {
        $config = $this->resolveSegment($segment);
        abort_unless($config !== null, 404);

        $groupKey = strtoupper(trim((string) $request->query('group_key')));
        $period = 'daily';
        $limit = 5;


        $misCodes = $this->resolveMisCodes($config);
        $asOfDate = $this->latestSubSegmentDate($misCodes) ?: $this->latestOverallDate();

        if (!$asOfDate || empty($misCodes)) {
            return response()->json([
                'message' => 'No segment data available.',
                'gainers' => [],
                'losers' => [],
            ]);
        }

        $payload = $this->buildPayload($config, $misCodes, $asOfDate);

        $selectedRow = collect($payload['tableRows'])
            ->first(fn($row) => strtoupper((string) $row['group_key']) === $groupKey);

        if (!$selectedRow) {
            return response()->json([
                'message' => 'Selected business segment group was not found.',
                'gainers' => [],
                'losers' => [],
            ], 404);
        }

        $endDate = Carbon::parse($payload['asOfDate'])->toDateString();
        $startDate = $this->resolveDriverStartDate($period, $endDate, $selectedRow['mis_codes']);

        if (!$startDate) {
            return response()->json([
                'message' => 'Insufficient history for this period.',
                'gainers' => [],
                'losers' => [],
            ]);
        }

        $drivers = $subSegmentMoversService->drilldownByMisCodes(
            $startDate,
            $endDate,
            $selectedRow['mis_codes'],
            $limit
        );

        return response()->json([
            'segment' => $config['label'],
            'group_key' => $selectedRow['group_key'],
            'group_label' => $selectedRow['label'],
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'movement' => round((float) ($selectedRow[$period . '_movement'] ?? 0), 2),
            'gainers' => $this->normalizeDriverRows($drivers['gainers'] ?? []),
            'losers' => $this->normalizeDriverRows($drivers['losers'] ?? []),
        ]);
    }

    private function resolveDriverStartDate(string $period, string $endDate, array $misCodes): ?string
    {
        $dates = SubSegmentMover::query()
            ->whereIn('mis_code', $misCodes)
            ->whereDate('end_date', '<=', $endDate)
            ->pluck('end_date')
            ->map(fn($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->sort()
            ->values()
            ->all();

        if (empty($dates)) {
            return null;
        }

        if ($period === 'daily') {
            return $this->latestAvailableDateOnOrBefore($dates, $endDate, true);
        }

        if ($period === 'mtd') {
            $target = Carbon::parse($endDate)->startOfMonth()->subDay()->toDateString();
            return $this->latestAvailableDateOnOrBefore($dates, $target);
        }

        if ($period === 'ytd') {
            $target = Carbon::parse($endDate)->startOfYear()->subDay()->toDateString();
            return $this->latestAvailableDateOnOrBefore($dates, $target);
        }

        return null;
    }

    private function buildSegmentLevelYtdCard(array $config, string $effectiveDate, string $accent): array
    {
        $currentRow = $this->fetchSegmentMoverAtOrBefore($config['code'], $effectiveDate);

        $ytdTarget = Carbon::parse($effectiveDate)
            ->startOfYear()
            ->subDay()
            ->toDateString();

        $openingRow = $this->fetchSegmentMoverAtOrBefore($config['code'], $ytdTarget);

        $current = $currentRow ? (float) $currentRow->end_balance : 0.0;
        $opening = $openingRow ? (float) $openingRow->end_balance : 0.0;

        return $this->movementCard(
            'YTD',
            $current,
            $opening,
            $openingRow ? Carbon::parse($openingRow->end_date)->toDateString() : null,
            $currentRow ? Carbon::parse($currentRow->end_date)->toDateString() : $effectiveDate,
            $accent
        );
    }

    private function fetchSegmentMoverAtOrBefore(string $segmentCode, string $targetDate): ?object
    {
        return DB::table('segment_movers')
            ->where('segment_code', strtoupper($segmentCode))
            ->whereDate('end_date', '<=', $targetDate)
            ->orderByDesc('end_date')
            ->first();
    }

    private function normalizeDriverRows($rows): array
    {
        return collect($rows)
            ->take(5)
            ->map(function ($row) {
                $item = is_array($row) ? $row : (array) $row;

                $customerName = $item['customer_name']
                    ?? $item['account_name']
                    ?? $item['customer']
                    ?? $item['name']
                    ?? $item['account_title']
                    ?? 'N/A';

                return [
                    'customer_name' => $customerName,
                    'start_balance' => round((float) ($item['start_balance'] ?? 0), 2),
                    'end_balance' => round((float) ($item['end_balance'] ?? 0), 2),
                    'movement' => round((float) ($item['movement'] ?? 0), 2),
                ];
            })
            ->values()
            ->all();
    }
}
