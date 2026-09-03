<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Reports\LoanDashboardService;
use App\Services\Reports\LoanMovementService;
use Carbon\Carbon;

class LoanSegmentController extends Controller
{
    public function __construct(
        private LoanDashboardService $dashboard,
        private LoanMovementService $movement,
    ) {
    }

    public function show(string $segment)
    {
        $resolved = LoanDashboardService::resolveSegmentSlug($segment);

        if (!$resolved) {
            abort(404);
        }

        $asOfDate = $this->dashboard->latestDate();

        if (!$asOfDate) {
            return view('finance.loans.segment', $this->emptyPayload($resolved));
        }

        return view('finance.loans.segment', $this->buildPayload($resolved, $asOfDate));
    }

    private function buildPayload(array $resolved, string $asOfDate): array
    {
        $dashboardPayload = $this->dashboard->buildDashboardPayload($asOfDate);
        $label = $resolved['label'];
        $canon = $resolved['canon'];
        $accent = $resolved['color'];

        // Reuse the daily comparison window the main dashboard already resolved
        // (it accounts for gaps in the import history), rather than re-deriving it.
        $dailyPeriods = $dashboardPayload['chartPayload']['overall']['daily']['periods'] ?? [];
        $lastDailyPeriod = !empty($dailyPeriods) ? end($dailyPeriods) : null;
        $dailyStart = $lastDailyPeriod['from'] ?? Carbon::parse($asOfDate)->subDay()->toDateString();

        $statusBreakdown = $this->movement->buildStatusBreakdownForSegment($dailyStart, $asOfDate, $canon);
        $combined = $this->movement->buildCombined($dailyStart, $asOfDate, 10, $canon);

        $segmentPie = $dashboardPayload['chartPayload']['segmentPie'] ?? ['labels' => [], 'data' => []];
        $pieIndex = array_search($label, $segmentPie['labels'] ?? [], true);
        $currentBalance = $pieIndex !== false ? (float) ($segmentPie['data'][$pieIndex] ?? 0) : 0.0;
        $pieTotal = array_sum(array_filter($segmentPie['data'] ?? [], 'is_numeric'));
        $percentage = $pieTotal > 0 ? round(($currentBalance / $pieTotal) * 100, 1) : null;

        $mtdYtd = $dashboardPayload['mtdYtdPayload'] ?? ['labels' => [], 'mtd' => [], 'ytd' => []];
        $mtdYtdIndex = array_search($label, $mtdYtd['labels'] ?? [], true);
        $mtdMovement = $mtdYtdIndex !== false ? (float) ($mtdYtd['mtd'][$mtdYtdIndex] ?? 0) : 0.0;
        $ytdMovement = $mtdYtdIndex !== false ? (float) ($mtdYtd['ytd'][$mtdYtdIndex] ?? 0) : 0.0;

        $trend = [];
        foreach (['daily', 'weekly', 'monthly'] as $period) {
            $trend[$period] = [
                'movement' => $this->pluckDataset($dashboardPayload['chartPayload']['segments'][$period] ?? [], $label),
                'closing'  => $this->pluckDataset($dashboardPayload['chartPayload']['overallBreakdown'][$period] ?? [], $label),
            ];
        }

        $dailyMovement = $this->pluckLatestValue($trend['daily']['movement']);
        $mtdStart = Carbon::parse($asOfDate)->startOfMonth()->subDay()->format('d M Y');
        $ytdStart = Carbon::parse($asOfDate)->startOfYear()->subDay()->format('d M Y');
        $asOfLabel = Carbon::parse($asOfDate)->format('d M Y');

        $summaryCards = [
            $this->movementCard('Daily Movement', $dailyMovement ?? 0.0, $currentBalance, $accent, Carbon::parse($dailyStart)->format('d M Y') . ' → ' . $asOfLabel),
            $this->movementCard('MTD Movement', $mtdMovement, $currentBalance, $accent, $mtdStart . ' → ' . $asOfLabel),
            $this->movementCard('YTD Movement', $ytdMovement, $currentBalance, $accent, $ytdStart . ' → ' . $asOfLabel),
            [
                'label' => 'Current Balance',
                'value' => $this->formatMoneyShort($currentBalance),
                'raw' => $currentBalance,
                'direction' => 'flat',
                'change_pct' => null,
                'range' => ($percentage !== null ? number_format($percentage, 1) . '% of performing loan book · ' : '') . 'As at ' . $asOfLabel,
                'accent' => $accent,
                'badge' => 'BALANCE',
            ],
        ];

        return [
            'asOfDate'        => $asOfDate,
            'segment'         => $resolved,
            'currentBalance'  => $currentBalance,
            'percentage'      => $percentage,
            'summaryCards'    => $summaryCards,
            'statusBreakdown' => $statusBreakdown,
            'topMovers'       => $combined['movers'] ?? ['gainers' => [], 'losers' => []],
            'trend'           => $trend,
        ];
    }

    /** Extracts one segment's {labels, data} series out of a multi-segment chart payload. */
    private function pluckDataset(array $payload, string $label): array
    {
        $labels  = $payload['labels'] ?? [];
        $dataset = collect($payload['datasets'] ?? [])->first(fn($set) => ($set['label'] ?? null) === $label);

        return [
            'labels' => $labels,
            'data'   => $dataset['data'] ?? [],
        ];
    }

    private function pluckLatestValue(array $series): ?float
    {
        $data = array_values(array_filter($series['data'] ?? [], fn($value) => $value !== null));

        return $data ? (float) end($data) : null;
    }

    private function movementCard(string $label, float $movement, float $currentBalance, string $accent, string $range): array
    {
        $previous = $currentBalance - $movement;
        $changePct = abs($previous) > 0.00001 ? round(($movement / abs($previous)) * 100, 2) : null;

        return [
            'label' => $label,
            'value' => $this->formatMoneyShort($movement),
            'raw' => $movement,
            'direction' => $movement >= 0 ? 'up' : 'down',
            'change_pct' => $changePct,
            'range' => $range,
            'accent' => $accent,
        ];
    }

    private function formatMoneyShort(float $value): string
    {
        $prefix = $value < 0 ? '-KES ' : 'KES ';
        $abs = abs($value);

        if ($abs >= 1_000_000_000) {
            return $prefix . number_format($abs / 1_000_000_000, 2) . 'B';
        }

        if ($abs >= 1_000_000) {
            return $prefix . number_format($abs / 1_000_000, 2) . 'M';
        }

        if ($abs >= 1_000) {
            return $prefix . number_format($abs / 1_000, 2) . 'K';
        }

        return $prefix . number_format($abs, 2);
    }

    private function emptyPayload(array $resolved): array
    {
        $emptySeries = ['labels' => [], 'data' => []];

        return [
            'asOfDate'       => null,
            'segment'        => $resolved,
            'currentBalance' => 0.0,
            'percentage'     => null,
            'summaryCards'   => [],
            'statusBreakdown' => [
                'categories'   => [],
                'startBalance' => 0.0,
                'endBalance'   => 0.0,
                'weekOnWeek'   => 0.0,
                'mtd'          => 0.0,
                'ytd'          => 0.0,
                'direction'    => 'FLAT',
            ],
            'topMovers' => ['gainers' => [], 'losers' => []],
            'trend' => [
                'daily'   => ['movement' => $emptySeries, 'closing' => $emptySeries],
                'weekly'  => ['movement' => $emptySeries, 'closing' => $emptySeries],
                'monthly' => ['movement' => $emptySeries, 'closing' => $emptySeries],
            ],
        ];
    }
}
