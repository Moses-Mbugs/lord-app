<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\SubSegmentMover;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceHomeController extends Controller
{
    private const SEGMENT_MAP = [
        'CB' => 'Corporate',
        'CM' => 'Commercial',
        'CS' => 'Consumer',
        'OT' => 'Others',
    ];

    private const SEGMENT_COLORS = [
        'CB' => '#005B82',
        'CM' => '#008FC7',
        'CS' => '#10B981',
        'OT' => '#BED600',
    ];

    private const GROUP_TYPE_BRANCH = 'BRANCH';
    private const GROUP_SCOPE = 'SUMMARY';

    private const SUMMARY_SCOPE_OVERALL = 'OVERALL';
    private const SUMMARY_SCOPE_SEGMENT = 'SEGMENT';
    private const SUMMARY_SEGMENT_ALL = 'ALL';

    public function index()
    {
        $asOfDate = $this->latestDate();

        if (!$asOfDate) {
            return view('finance.dashboard', $this->emptyPayload());
        }

        return view('finance.dashboard', $this->buildPayload($asOfDate));
    }

    public function floatLive()
    {
        $asOfDate = $this->latestDate();

        if (!$asOfDate) {
            return response()->json(['status' => 'empty']);
        }

        $payload = $this->buildPayload($asOfDate);
        $cards = $payload['summaryCards'];

        return response()->json([
            'status' => 'ok',
            'as_of' => $asOfDate,
            'daily' => $cards[0] ?? null,
            'mtd' => $cards[1] ?? null,
            'ytd' => $cards[2] ?? null,
            'total_deposits' => $cards[3] ?? null,
            'currency_mix' => $cards[4] ?? null,
            'deposit_mix' => $cards[5] ?? null,
        ]);
    }

    public function segmentData(Request $request)
    {
        $map = [
            'corporate' => 'CB',
            'commercial' => 'CM',
            'consumer' => 'CS',
            'others' => 'OT',
            'cb' => 'CB',
            'cm' => 'CM',
            'cs' => 'CS',
            'ot' => 'OT',
        ];

        $segmentCode = $map[strtolower((string) $request->query('segment', 'consumer'))] ?? 'CS';
        $asOfDate = $this->latestDate();

        if (!$asOfDate) {
            return response()->json([]);
        }

        $historyStart = Carbon::parse($asOfDate)->subMonths(15)->startOfMonth()->toDateString();
        $segmentRows = $this->fetchSegmentMoverRows($historyStart, $asOfDate);
        $segmentSeries = $this->segmentSeriesToBalanceMap($segmentRows);
        $series = $segmentSeries[$segmentCode] ?? [];
        $allDates = array_keys($series);
        sort($allDates);

        $current = (float) ($series[$asOfDate] ?? 0);
        $dailyStart = $this->latestAvailableDateOnOrBefore($allDates, $asOfDate, true);
        $mtdStart = $this->latestAvailableDateOnOrBefore(
            $allDates,
            Carbon::parse($asOfDate)->startOfMonth()->subDay()->toDateString()
        );
        $ytdStart = $this->latestAvailableDateOnOrBefore(
            $allDates,
            Carbon::parse($asOfDate)->startOfYear()->subDay()->toDateString()
        );

        return response()->json([
            'segment_code' => $segmentCode,
            'segment_name' => self::SEGMENT_MAP[$segmentCode] ?? 'Others',
            'as_of' => $asOfDate,
            'closing_balance' => $current,
            'daily_movement' => $dailyStart ? round($current - (float) ($series[$dailyStart] ?? 0), 2) : 0,
            'mtd_movement' => $mtdStart ? round($current - (float) ($series[$mtdStart] ?? 0), 2) : 0,
            'ytd_movement' => $ytdStart ? round($current - (float) ($series[$ytdStart] ?? 0), 2) : 0,
        ]);
    }

    public function subSegmentModal(Request $request): JsonResponse
    {
        $request->validate([
            'segment' => ['required', 'string', 'max:255'],
        ]);

        $segment = $request->input('segment');

        $latestEndDate = SubSegmentMover::query()
            ->where('business_segment_name', $segment)
            ->max('end_date');

        if (!$latestEndDate) {
            return response()->json([
                'segment' => $segment,
                'as_of' => null,
                'sub_segments' => [],
                'total' => 0,
            ]);
        }

        $latestStartDate = SubSegmentMover::query()
            ->where('business_segment_name', $segment)
            ->where('end_date', $latestEndDate)
            ->min('start_date');

        $rows = SubSegmentMover::query()
            ->where('business_segment_name', $segment)
            ->where('start_date', $latestStartDate)
            ->where('end_date', $latestEndDate)
            ->orderByDesc('end_balance')
            ->get([
                'mis_code',
                'code_desc',
                'end_balance',
                'cif_count',
            ]);

        $total = 0;
        foreach ($rows as $row) {
            $total += (float) $row->end_balance;
        }

        $subSegments = $rows->map(function ($row) use ($total) {
            $bal = (float) $row->end_balance;

            return [
                'mis_code' => $row->mis_code,
                'desc' => $row->code_desc ?: $row->mis_code,
                'end_balance' => round($bal, 2),
                'cif_count' => (int) $row->cif_count,
                'share_pct' => $total > 0 ? round(($bal / $total) * 100, 1) : 0,
            ];
        })->values()->all();

        return response()->json([
            'segment' => $segment,
            'as_of' => Carbon::parse($latestEndDate)->format('d M Y'),
            'sub_segments' => $subSegments,
            'total' => round($total, 2),
        ]);
    }

    private function buildPayload(string $asOfDate): array
    {
        $historyStart = Carbon::parse($asOfDate)->subMonths(15)->startOfMonth()->toDateString();

        $overallRows = $this->fetchOverallRows($historyStart, $asOfDate);
        $segmentRows = $this->fetchSegmentMoverRows($historyStart, $asOfDate);
        $branchRows = $this->fetchGroupMoverRows(
            self::GROUP_TYPE_BRANCH,
            $historyStart,
            $asOfDate,
            self::GROUP_SCOPE
        );

        $overallSeries = $this->overallSeriesToBalanceMap($overallRows);
        $segmentSeries = $this->segmentSeriesToBalanceMap($segmentRows);
        $branchSeries = $this->branchSeriesToBalanceMap($branchRows);

        $segmentDates = [];
        foreach ($segmentSeries as $series) {
            $segmentDates = array_merge($segmentDates, array_keys($series));
        }

        $dateKeys = array_values(array_unique(array_merge(
            array_keys($overallSeries),
            $segmentDates
        )));
        sort($dateKeys);

        $dailyClosings = $this->buildPeriodClosings($dateKeys, 'daily', 30);
        $weeklyClosings = $this->buildPeriodClosings($dateKeys, 'weekly', 12);
        $monthlyClosings = $this->buildPeriodClosings($dateKeys, 'monthly', 12);

        $eoyBaseline = $this->resolveEoyBaselineClosing($dateKeys, $asOfDate);

        $dailyBreakdownClosings = $this->prependBaselineClosing($dailyClosings, $eoyBaseline);
        $weeklyBreakdownClosings = $this->prependBaselineClosing($weeklyClosings, $eoyBaseline);
        $monthlyBreakdownClosings = $this->prependBaselineClosing($monthlyClosings, $eoyBaseline);

        $mixSummary = $this->fetchDailyMixSummaryOnOrBefore($asOfDate);

        return [
            'asOfDate' => $asOfDate,
            'summaryCards' => $this->buildSummaryCards($overallSeries, $asOfDate, $mixSummary),
            'mtdYtdPayload' => $this->buildMtdYtdPayload($segmentSeries, $asOfDate),
            'chartPayload' => [
                'overall' => [
                    'daily' => $this->buildOverallMovementPayload($overallSeries, $dailyClosings),
                    'weekly' => $this->buildOverallMovementPayload($overallSeries, $weeklyClosings),
                    'monthly' => $this->buildOverallMovementPayload($overallSeries, $monthlyClosings),
                ],
                'overallBreakdown' => [
                    'daily' => $this->buildOverallBreakdownPayload($segmentSeries, $dailyBreakdownClosings),
                    'weekly' => $this->buildOverallBreakdownPayload($segmentSeries, $weeklyBreakdownClosings),
                    'monthly' => $this->buildOverallBreakdownPayload($segmentSeries, $monthlyBreakdownClosings),
                ],
                'segments' => [
                    'daily' => $this->buildSegmentMovementPayload($segmentRows, $segmentSeries, $dailyClosings),
                    'weekly' => $this->buildSegmentMovementPayload($segmentRows, $segmentSeries, $weeklyClosings),
                    'monthly' => $this->buildSegmentMovementPayload($segmentRows, $segmentSeries, $monthlyClosings),
                ],
                'branches' => [
                    'daily' => $this->buildBranchSnapshotPayload($branchSeries, $dailyClosings),
                    'weekly' => $this->buildBranchSnapshotPayload($branchSeries, $weeklyClosings),
                    'monthly' => $this->buildBranchSnapshotPayload($branchSeries, $monthlyClosings),
                ],
                'segmentPie' => $this->buildSegmentPiePayload($segmentSeries, $asOfDate),
                'currencyMixPie' => $this->buildCurrencyMixPiePayload($mixSummary),
                'depositMixPie' => $this->buildDepositMixPiePayload($mixSummary),
            ],
        ];
    }

    private function latestDate(): ?string
    {
        $date = DB::table('segment_movers')->max('end_date');

        return $date ? Carbon::parse((string) $date)->toDateString() : null;
    }

    private function fetchDailyMixSummaryOnOrBefore(string $asOfDate, string $scope = self::SUMMARY_SCOPE_OVERALL, string $segmentCode = self::SUMMARY_SEGMENT_ALL): array
    {
        $row = DB::table('finance_daily_mix_summaries')
            ->whereDate('balance_date', '<=', $asOfDate)
            ->where('summary_scope', strtoupper($scope))
            ->where('segment_code', strtoupper($segmentCode))
            ->orderByDesc('balance_date')
            ->first();

        if (!$row) {
            return $this->emptyDailyMixSummary($scope, $segmentCode);
        }

        return [
            'balance_date' => $row->balance_date ? Carbon::parse($row->balance_date)->toDateString() : null,
            'summary_scope' => strtoupper((string) ($row->summary_scope ?? self::SUMMARY_SCOPE_OVERALL)),
            'segment_code' => strtoupper((string) ($row->segment_code ?? self::SUMMARY_SEGMENT_ALL)),
            'segment_name' => (string) ($row->segment_name ?? self::SEGMENT_MAP[strtoupper($segmentCode)] ?? 'Overall'),
            'lcy_amount' => round((float) ($row->lcy_amount ?? 0), 2),
            'fcy_amount' => round((float) ($row->fcy_amount ?? 0), 2),
            'lcy_pct' => round((float) ($row->lcy_pct ?? 0), 2),
            'fcy_pct' => round((float) ($row->fcy_pct ?? 0), 2),
            'current_amount' => round((float) ($row->current_amount ?? 0), 2),
            'savings_amount' => round((float) ($row->savings_amount ?? 0), 2),
            'term_amount' => round((float) ($row->term_amount ?? 0), 2),
            'current_pct' => round((float) ($row->current_pct ?? 0), 2),
            'savings_pct' => round((float) ($row->savings_pct ?? 0), 2),
            'term_pct' => round((float) ($row->term_pct ?? 0), 2),
            'total_positive_lcy_balance' => round((float) ($row->total_positive_lcy_balance ?? 0), 2),
            'source_row_count' => (int) ($row->source_row_count ?? 0),
            'currency_mix_json' => $this->decodeJsonToArray($row->currency_mix_json ?? null),
            'deposit_mix_json' => $this->decodeJsonToArray($row->deposit_mix_json ?? null),
            'last_built_at' => $row->last_built_at ?? null,
        ];
    }

    private function emptyDailyMixSummary(string $scope = self::SUMMARY_SCOPE_OVERALL, string $segmentCode = self::SUMMARY_SEGMENT_ALL): array
    {
        return [
            'balance_date' => null,
            'summary_scope' => strtoupper($scope),
            'segment_code' => strtoupper($segmentCode),
            'segment_name' => strtoupper($scope) === self::SUMMARY_SCOPE_SEGMENT
                ? (self::SEGMENT_MAP[strtoupper($segmentCode)] ?? 'Unknown')
                : 'Overall',
            'lcy_amount' => 0.0,
            'fcy_amount' => 0.0,
            'lcy_pct' => 0.0,
            'fcy_pct' => 0.0,
            'current_amount' => 0.0,
            'savings_amount' => 0.0,
            'term_amount' => 0.0,
            'current_pct' => 0.0,
            'savings_pct' => 0.0,
            'term_pct' => 0.0,
            'total_positive_lcy_balance' => 0.0,
            'source_row_count' => 0,
            'currency_mix_json' => null,
            'deposit_mix_json' => null,
            'last_built_at' => null,
        ];
    }

    private function decodeJsonToArray($value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded)
            ? $decoded
            : null;
    }

    private function fetchOverallRows(string $startDate, string $endDate): \Illuminate\Support\Collection
    {
        return DB::table('segment_movers')
            ->where('segment_code', 'ALL')
            ->whereBetween('end_date', [$startDate, $endDate])
            ->select('start_date', 'end_date', 'start_balance', 'end_balance', 'movement')
            ->orderBy('end_date')
            ->get();
    }

    private function fetchSegmentMoverRows(string $startDate, string $endDate): \Illuminate\Support\Collection
    {
        return DB::table('segment_movers')
            ->whereIn('segment_code', ['CB', 'CM', 'CS', 'OT'])
            ->whereBetween('end_date', [$startDate, $endDate])
            ->select('segment_code', 'segment_name', 'start_date', 'end_date', 'start_balance', 'end_balance', 'movement', 'cif_count')
            ->orderBy('end_date')
            ->get();
    }

    private function fetchGroupMoverRows(
        string $groupType,
        string $startDate,
        string $endDate,
        string $scope = 'SUMMARY'
    ): \Illuminate\Support\Collection {
        return DB::table('group_movers')
            ->where('group_type', $groupType)
            ->where('scope', $scope)
            ->where('group_key', '<>', 'ALL')
            ->whereBetween('end_date', [$startDate, $endDate])
            ->select('group_key', 'group_name', 'start_date', 'end_date', 'start_balance', 'end_balance', 'movement')
            ->orderBy('end_date')
            ->get();
    }

    private function overallSeriesToBalanceMap(\Illuminate\Support\Collection $rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row->end_date] = round((float) $row->end_balance, 2);
        }
        ksort($map);

        return $map;
    }

    private function segmentSeriesToBalanceMap(\Illuminate\Support\Collection $rows): array
    {
        $map = ['CB' => [], 'CM' => [], 'CS' => [], 'OT' => []];

        foreach ($rows as $row) {
            $code = strtoupper((string) ($row->segment_code ?? 'OT'));

            if (!isset($map[$code])) {
                $map[$code] = [];
            }

            $map[$code][(string) $row->end_date] = round((float) $row->end_balance, 2);
        }

        return $map;
    }

    private function branchSeriesToBalanceMap(\Illuminate\Support\Collection $rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            $code = strtoupper((string) ($row->group_key ?? 'UNK'));

            if (!isset($map[$code])) {
                $map[$code] = [
                    'name' => (string) ($row->group_name ?? $code),
                    'dates' => [],
                ];
            }

            $map[$code]['dates'][(string) $row->end_date] = round((float) $row->end_balance, 2);
        }

        return $map;
    }

    private function buildPeriodClosings(array $dateKeys, string $mode, int $points): array
    {
        sort($dateKeys);

        if (empty($dateKeys)) {
            return [];
        }

        if ($mode === 'daily') {
            $closures = array_map(fn($d) => [
                'date' => $d,
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

    private function buildOverallMovementPayload(array $dailyBalances, array $periodClosings): array
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
            $to = $periodClosings[$i]['date'];

            $labels[] = $periodClosings[$i]['label'];
            $data[] = round((float) ($dailyBalances[$to] ?? 0) - (float) ($dailyBalances[$from] ?? 0), 2);
            $closingBalances[] = round((float) ($dailyBalances[$to] ?? 0), 2);
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
                'date' => $point['date'],
                'is_baseline' => !empty($point['is_baseline']),
            ];
        }

        $datasets = [];

        foreach (self::SEGMENT_MAP as $code => $name) {
            $series = $segmentSeries[$code] ?? [];
            $dates = array_keys($series);
            sort($dates);

            $values = [];
            $colors = [];

            foreach ($periodClosings as $point) {
                $effective = $this->latestAvailableDateOnOrBefore($dates, $point['date']);

                $values[] = $effective
                    ? round((float) ($series[$effective] ?? 0), 2)
                    : 0;

                $colors[] = !empty($point['is_baseline'])
                    ? '#94A3B8'
                    : self::SEGMENT_COLORS[$code];
            }

            $datasets[] = [
                'label' => $name,
                'data' => $values,
                'color' => self::SEGMENT_COLORS[$code],
                'colors' => $colors,
            ];
        }

        return compact('labels', 'datasets', 'periods');
    }

    private function buildSegmentMovementPayload(
        \Illuminate\Support\Collection $segmentRows,
        array $segmentSeries,
        array $periodClosings
    ): array {
        if (count($periodClosings) < 2) {
            return ['labels' => [], 'datasets' => [], 'periods' => [], 'cifCounts' => []];
        }

        $labels = [];
        $periods = [];

        for ($i = 1; $i < count($periodClosings); $i++) {
            $labels[] = $periodClosings[$i]['label'];
            $periods[] = ['from' => $periodClosings[$i - 1]['date'], 'to' => $periodClosings[$i]['date']];
        }

        $cifCounts = [];
        foreach ($segmentRows as $row) {
            $name = self::SEGMENT_MAP[strtoupper((string) $row->segment_code)] ?? 'Others';
            $cifCounts[$name] = (int) ($row->cif_count ?? 0);
        }

        $datasets = [];
        foreach (self::SEGMENT_MAP as $code => $name) {
            $series = [];

            for ($i = 1; $i < count($periodClosings); $i++) {
                $from = $periodClosings[$i - 1]['date'];
                $to = $periodClosings[$i]['date'];

                $series[] = round(
                    (float) ($segmentSeries[$code][$to] ?? 0) - (float) ($segmentSeries[$code][$from] ?? 0),
                    2
                );
            }

            $datasets[] = [
                'label' => $name,
                'data' => $series,
                'color' => self::SEGMENT_COLORS[$code],
            ];
        }

        return compact('labels', 'datasets', 'periods', 'cifCounts');
    }

    private function buildBranchSnapshotPayload(array $branchSeries, array $periodClosings): array
    {
        if (count($periodClosings) < 2) {
            return ['labels' => [], 'data' => [], 'colors' => [], 'from' => null, 'to' => null];
        }

        $fromDate = $periodClosings[count($periodClosings) - 2]['date'];
        $toDate = $periodClosings[count($periodClosings) - 1]['date'];

        $rows = [];
        foreach ($branchSeries as $code => $info) {
            $current = (float) ($info['dates'][$toDate] ?? 0);
            $previous = (float) ($info['dates'][$fromDate] ?? 0);

            if ($current === 0.0 && $previous === 0.0) {
                continue;
            }

            $movement = round($current - $previous, 2);
            $rows[] = [
                'label' => $info['name'] ?: $code,
                'value' => $movement,
                'abs' => abs($movement),
            ];
        }

        usort($rows, fn($a, $b) => $b['abs'] <=> $a['abs']);

        return [
            'labels' => array_map(fn($row) => $row['label'], $rows),
            'data' => array_map(fn($row) => $row['value'], $rows),
            'colors' => array_map(fn($row) => match (true) {
                $row['value'] > 0 => '#10B981',
                $row['value'] < 0 => '#EF4444',
                default => '#EDEDED',
            }, $rows),
            'from' => $fromDate,
            'to' => $toDate,
        ];
    }

    private function buildSegmentPiePayload(array $segmentSeries, string $asOfDate): array
    {
        $labels = [];
        $data = [];
        $colors = [];

        foreach (self::SEGMENT_MAP as $code => $name) {
            $series = $segmentSeries[$code] ?? [];
            $dates = array_keys($series);
            sort($dates);

            $effective = $this->latestAvailableDateOnOrBefore($dates, $asOfDate);
            $balance = $effective ? round((float) ($series[$effective] ?? 0), 2) : 0;

            if ($balance > 0) {
                $labels[] = $name;
                $data[] = $balance;
                $colors[] = self::SEGMENT_COLORS[$code];
            }
        }

        return compact('labels', 'data', 'colors');
    }

    private function buildCurrencyMixPiePayload(array $mixSummary): array
    {
        if (!$mixSummary['balance_date']) {
            return ['labels' => [], 'data' => [], 'colors' => []];
        }

        return $this->normalizeStoredPiePayload(
            $mixSummary['currency_mix_json'],
            [
                'LCY' => ['value' => $mixSummary['lcy_amount'], 'color' => '#005B82'],
                'FCY' => ['value' => $mixSummary['fcy_amount'], 'color' => '#10B981'],
            ]
        );
    }

    private function buildDepositMixPiePayload(array $mixSummary): array
    {
        if (!$mixSummary['balance_date']) {
            return ['labels' => [], 'data' => [], 'colors' => []];
        }

        return $this->normalizeStoredPiePayload(
            $mixSummary['deposit_mix_json'],
            [
                'Current Account' => ['value' => $mixSummary['current_amount'], 'color' => '#005B82'],
                'Savings Account' => ['value' => $mixSummary['savings_amount'], 'color' => '#008FC7'],
                'Term Deposit' => ['value' => $mixSummary['term_amount'], 'color' => '#10B981'],
            ]
        );
    }

    private function normalizeStoredPiePayload(?array $stored, array $fallbackMap): array
    {
        if ($stored) {
            if (isset($stored['labels'], $stored['data']) && is_array($stored['labels']) && is_array($stored['data'])) {
                return [
                    'labels' => array_values($stored['labels']),
                    'data' => array_map(fn($v) => round((float) $v, 2), array_values($stored['data'])),
                    'colors' => isset($stored['colors']) && is_array($stored['colors'])
                        ? array_values($stored['colors'])
                        : array_fill(0, count($stored['labels']), '#0082BB'),
                ];
            }

            if (isset($stored['labels'], $stored['amounts']) && is_array($stored['labels']) && is_array($stored['amounts'])) {
                return [
                    'labels' => array_values($stored['labels']),
                    'data' => array_map(fn($v) => round((float) $v, 2), array_values($stored['amounts'])),
                    'colors' => isset($stored['colors']) && is_array($stored['colors'])
                        ? array_values($stored['colors'])
                        : array_fill(0, count($stored['labels']), '#0082BB'),
                ];
            }
        }

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($fallbackMap as $label => $meta) {
            $value = (float) ($meta['value'] ?? 0);
            if ($value <= 0) {
                continue;
            }

            $labels[] = $label;
            $data[] = round($value, 2);
            $colors[] = (string) ($meta['color'] ?? '#0082BB');
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

        foreach (self::SEGMENT_MAP as $code => $name) {
            $series = $segmentSeries[$code] ?? [];
            $dates = array_keys($series);
            sort($dates);

            $effectiveDate = $this->latestAvailableDateOnOrBefore($dates, $asOfDate);
            $currentVal = $effectiveDate ? (float) ($series[$effectiveDate] ?? 0) : 0.0;

            $mtdStart = $this->latestAvailableDateOnOrBefore($dates, $mtdStartDate);
            $ytdStart = $this->latestAvailableDateOnOrBefore($dates, $ytdStartDate);

            $labels[] = $name;
            $mtd[] = $mtdStart ? round($currentVal - (float) ($series[$mtdStart] ?? 0), 2) : 0;
            $ytd[] = $ytdStart ? round($currentVal - (float) ($series[$ytdStart] ?? 0), 2) : 0;
            $colors[] = self::SEGMENT_COLORS[$code];
        }

        return compact('labels', 'mtd', 'ytd', 'colors');
    }

    private function buildSummaryCards(array $overallSeries, string $asOfDate, array $mixSummary): array
    {
        $allDates = array_keys($overallSeries);
        sort($allDates);

        $effectiveDate = $this->latestAvailableDateOnOrBefore($allDates, $asOfDate) ?? $asOfDate;
        $current = (float) ($overallSeries[$effectiveDate] ?? 0);

        $dailyStart = $this->latestAvailableDateOnOrBefore($allDates, $effectiveDate, true);
        $mtdStart = $this->latestAvailableDateOnOrBefore(
            $allDates,
            Carbon::parse($asOfDate)->startOfMonth()->subDay()->toDateString()
        );
        $ytdStart = $this->latestAvailableDateOnOrBefore(
            $allDates,
            Carbon::parse($asOfDate)->startOfYear()->subDay()->toDateString()
        );

        return [
            $this->buildMovementCard('Daily Movement', $current, (float) ($overallSeries[$dailyStart] ?? 0), $dailyStart, $effectiveDate, '#0082BB'),
            $this->buildMovementCard('MTD Movement', $current, (float) ($overallSeries[$mtdStart] ?? 0), $mtdStart, $effectiveDate, '#10B981'),
            $this->buildMovementCard('YTD Movement', $current, (float) ($overallSeries[$ytdStart] ?? 0), $ytdStart, $effectiveDate, '#F59E0B'),

            [
                'label' => 'Total Deposits',
                'value' => $this->formatMoneyShort($current),
                'raw' => round($current, 2),
                'direction' => 'flat',
                'change_pct' => null,
                'range' => 'Closing balance · ' . Carbon::parse($effectiveDate)->format('d M Y'),
                'accent' => '#005B82',
                'badge' => 'TOTAL',
            ],
            $this->buildCurrencyMixCard($mixSummary),
            $this->buildDepositMixCard($mixSummary),
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
        // No baseline date on/before the comparison window (e.g. YTD before
        // segment_movers has any December-of-last-year history yet) — don't
        // fall back to comparing against 0, which would silently render the
        // whole current balance as if it were the period's movement.
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

    private function buildCurrencyMixCard(array $mixSummary): array
    {
        if (!$mixSummary['balance_date']) {
            return [
                'label' => 'Currency Mix',
                'value' => 'Pending',
                'raw' => null,
                'direction' => 'flat',
                'change_pct' => null,
                'range' => 'Daily mix summary not built',
                'accent' => '#005B82',
                'is_placeholder' => true,
            ];
        }

        $lcyPct = round((float) ($mixSummary['lcy_pct'] ?? 0), 1);
        $fcyPct = round((float) ($mixSummary['fcy_pct'] ?? 0), 1);

        return [
            'label' => 'Currency Mix',
            'value' => 'LCY ' . number_format($lcyPct, 1) . "%\nFCY " . number_format($fcyPct, 1) . '%',
            'raw' => round((float) ($mixSummary['total_positive_lcy_balance'] ?? 0), 2),
            'direction' => 'flat',
            'change_pct' => null,
            'range' => 'Positive LCY balances · ' . Carbon::parse($mixSummary['balance_date'])->format('d M Y'),
            'accent' => '#005B82',
        ];
    }

    private function buildDepositMixCard(array $mixSummary): array
    {
        if (!$mixSummary['balance_date']) {
            return [
                'label' => 'Deposit Mix',
                'value' => 'Pending',
                'raw' => null,
                'direction' => 'flat',
                'change_pct' => null,
                'range' => 'Daily mix summary not built',
                'accent' => '#10B981',
                'is_placeholder' => true,
            ];
        }

        $currentPct = round((float) ($mixSummary['current_pct'] ?? 0), 1);
        $savingsPct = round((float) ($mixSummary['savings_pct'] ?? 0), 1);
        $termPct = round((float) ($mixSummary['term_pct'] ?? 0), 1);

        $depositTotal = round(
            (float) ($mixSummary['current_amount'] ?? 0) +
                (float) ($mixSummary['savings_amount'] ?? 0) +
                (float) ($mixSummary['term_amount'] ?? 0),
            2
        );

        return [
            'label' => 'Deposit Mix',
            // buildDepositMixCard()
            'value' => 'CA ' . number_format($currentPct, 1) . "%\nSA " . number_format($savingsPct, 1) . "%\nTD " . number_format($termPct, 1) . '%',
            'raw' => $depositTotal,
            'direction' => 'flat',
            'change_pct' => null,
            'range' => 'GL 211/212/213 only · ' . Carbon::parse($mixSummary['balance_date'])->format('d M Y'),
            'accent' => '#10B981',
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

    private function emptyPayload(): array
    {
        return [
            'asOfDate' => null,
            'summaryCards' => [],
            'mtdYtdPayload' => ['labels' => [], 'mtd' => [], 'ytd' => [], 'colors' => []],
            'chartPayload' => [
                'overall' => ['daily' => [], 'weekly' => [], 'monthly' => []],
                'overallBreakdown' => ['daily' => [], 'weekly' => [], 'monthly' => []],
                'segments' => ['daily' => [], 'weekly' => [], 'monthly' => []],
                'branches' => ['daily' => [], 'weekly' => [], 'monthly' => []],
                'segmentPie' => ['labels' => [], 'data' => [], 'colors' => []],
                'currencyMixPie' => ['labels' => [], 'data' => [], 'colors' => []],
                'depositMixPie' => ['labels' => [], 'data' => [], 'colors' => []],
            ],
        ];
    }

    private function resolveEoyBaselineClosing(array $dateKeys, string $asOfDate): ?array
    {
        $targetDate = Carbon::parse($asOfDate)
            ->startOfYear()
            ->subDay()
            ->toDateString();

        // $targetDate = '2025-12-30';

        // $baselineDate = in_array($targetDate, $dateKeys, true) ? $targetDate : null;

        $baselineDate = $this->latestAvailableDateOnOrBefore($dateKeys, $targetDate);

        if (!$baselineDate) {
            return null;
        }

        return [
            'date' => $baselineDate,
            'label' => 'EOY ' . Carbon::parse($baselineDate)->format('Y'),
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
}
