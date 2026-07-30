<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\SubSegmentMover;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExecSubSegmentController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        return view('finance.exec-sub-segment-dashboard');
    }

    public function data(Request $request): JsonResponse
    {
        $request->validate([
            'period'  => ['nullable', 'string', 'in:daily,weekly,monthly,quarterly,annually'],
            'segment' => ['nullable', 'string', 'max:255'],
            'from'    => ['nullable', 'date'],
            'to'      => ['nullable', 'date'],
            'n'       => ['nullable', 'integer', 'min:0'],
        ]);

        $segment = $request->input('segment', '');
        $n       = (int) $request->input('n', 10);
        $from    = $request->input('from');
        $to      = $request->input('to');

        // ── 1. Distinct (start_date, end_date) pairs ──────────────────────
        $pairsQuery = SubSegmentMover::query()
            ->select('start_date', 'end_date')
            ->distinct()
            ->orderBy('end_date');

        if ($from) $pairsQuery->whereDate('end_date', '>=', $from);
        if ($to)   $pairsQuery->whereDate('end_date', '<=', $to);

        $pairs = $pairsQuery->get();

        if ($n > 0) {
            $pairs = $pairs->slice(max(0, $pairs->count() - $n))->values();
        }

        $empty = [
            'period_labels'      => [],
            'seg_chart'          => [],
            'seg_totals'         => [],
            'sub_trend'          => [],
            'doughnut'           => [],
            'tree'               => [],
            'grand_totals'       => [],
            'kpi'                => (object) [],
            'available_segments' => [],
        ];

        if ($pairs->isEmpty()) {
            return response()->json($empty);
        }

        // ── 2. Load all rows for those pairs ──────────────────────────────
        $startDates = $pairs->pluck('start_date')->unique()->values()->all();
        $endDates   = $pairs->pluck('end_date')->unique()->values()->all();

        $allRowsQuery = SubSegmentMover::query()
            ->whereIn('start_date', $startDates)
            ->whereIn('end_date', $endDates);

        if ($segment) {
            $allRowsQuery->where('business_segment_name', $segment);
        }

        $allRows = $allRowsQuery->get();

        if ($allRows->isEmpty()) {
            return response()->json($empty);
        }

        // ── 3. Period labels ──────────────────────────────────────────────
        $pairsList    = $pairs->all();
        $periodLabels = $pairs->map(
            fn($p) => \Carbon\Carbon::parse($p->end_date)->format('d M Y')
        )->values()->all();

        $rowsForPair = fn(string $start, string $end) => $allRows->filter(
            fn($r) => $r->start_date == $start && $r->end_date == $end
        );

        $labelIndex = fn($row) => collect($pairsList)->search(
            fn($p) => $p->start_date == $row->start_date && $p->end_date == $row->end_date
        );

        // ── 4. Available segments ─────────────────────────────────────────
        $segments = $allRows
            ->pluck('business_segment_name')
            ->unique()
            ->filter()
            ->sort()
            ->values()
            ->all();

        // ── 5. seg_totals ─────────────────────────────────────────────────
        $segTotals = [];
        foreach ($segments as $seg) {
            foreach ($pairsList as $i => $pair) {
                $lbl     = $periodLabels[$i];
                $segRows = $rowsForPair($pair->start_date, $pair->end_date)
                    ->where('business_segment_name', $seg);

                $segTotals[$seg][$lbl] = [
                    'end_balance' => round((float) $segRows->sum('end_balance'), 2),
                    'movement'    => round((float) $segRows->sum('movement'), 2),
                ];
            }
        }

        // ── 6. seg_chart ──────────────────────────────────────────────────
        $segChart = collect($segments)->map(fn($seg) => [
            'segment' => $seg,
            'data'    => collect($periodLabels)
                ->map(fn($lbl) => $segTotals[$seg][$lbl]['end_balance'] ?? 0)
                ->values()
                ->all(),
        ])->values()->all();

        // ── 7. Doughnut (latest period) ───────────────────────────────────
        $lastPair  = $pairsList[count($pairsList) - 1];
        $lastLabel = $periodLabels[count($periodLabels) - 1];

        $doughnut = collect($segments)->map(fn($seg) => [
            'segment' => $seg,
            'value'   => $segTotals[$seg][$lastLabel]['end_balance'] ?? 0,
        ])->filter(fn($d) => $d['value'] > 0)->values()->all();

        // ── 8. sub_trend ──────────────────────────────────────────────────
        $subTrend  = [];
        $misGroups = $allRows->groupBy('mis_code');

        foreach ($misGroups as $mis => $misRows) {
            $first = $misRows->first();
            $data  = collect($pairsList)->map(function ($pair) use ($misRows) {
                $row = $misRows
                    ->where('start_date', $pair->start_date)
                    ->where('end_date', $pair->end_date)
                    ->first();
                return $row ? round((float) $row->movement, 2) : 0;
            })->values()->all();

            $subTrend[] = [
                'mis_code' => $mis,
                'label'    => $mis . ' - ' . ($first->code_desc ?? ''),
                'segment'  => $first->business_segment_name ?? '',
                'data'     => $data,
            ];
        }

        // ── 9. tree ───────────────────────────────────────────────────────
        $tree = [];
        foreach ($allRows as $row) {
            $seg = $row->business_segment_name;
            $mis = $row->mis_code;
            $idx = $labelIndex($row);

            if ($idx === false) continue;

            $lbl = $periodLabels[$idx];

            if (!isset($tree[$seg][$mis])) {
                $tree[$seg][$mis] = [
                    'mis_code' => $mis,
                    'desc'     => $row->code_desc,
                    'periods'  => [],
                ];
            }

            $tree[$seg][$mis]['periods'][$lbl] = [
                'end_balance' => round((float) $row->end_balance, 2),
                'movement'    => round((float) $row->movement, 2),
            ];
        }

        // ── 10. grand_totals ──────────────────────────────────────────────
        $grandTotals = [];
        foreach ($pairsList as $i => $pair) {
            $lbl      = $periodLabels[$i];
            $pairRows = $rowsForPair($pair->start_date, $pair->end_date);

            $grandTotals[$lbl] = [
                'end_balance' => round((float) $pairRows->sum('end_balance'), 2),
                'movement'    => round((float) $pairRows->sum('movement'), 2),
            ];
        }

        // ── 11. KPI (latest period) ───────────────────────────────────────
        $lastPairRows = $rowsForPair($lastPair->start_date, $lastPair->end_date);
        $bestSegRow   = null;
        $worstSegRow  = null;

        foreach ($segments as $seg) {
            $mov = $segTotals[$seg][$lastLabel]['movement'] ?? 0;
            if ($bestSegRow === null || $mov > $bestSegRow['movement']) {
                $bestSegRow = ['segment' => $seg, 'movement' => $mov];
            }
            if ($worstSegRow === null || $mov < $worstSegRow['movement']) {
                $worstSegRow = ['segment' => $seg, 'movement' => $mov];
            }
        }

        $kpi = [
            'total_balance'  => round((float) $lastPairRows->sum('end_balance'), 2),
            'total_movement' => round((float) $lastPairRows->sum('movement'), 2),
            'period_label'   => $lastLabel,
            'best_segment'   => $bestSegRow['segment']   ?? null,
            'best_movement'  => $bestSegRow['movement']  ?? null,
            'worst_segment'  => $worstSegRow['segment']  ?? null,
            'worst_movement' => $worstSegRow['movement'] ?? null,
        ];

        return response()->json([
            'period_labels'      => $periodLabels,
            'seg_chart'          => $segChart,
            'seg_totals'         => $segTotals,
            'sub_trend'          => $subTrend,
            'doughnut'           => $doughnut,
            'tree'               => $tree,
            'grand_totals'       => $grandTotals,
            'kpi'                => $kpi,
            'available_segments' => $segments,
        ]);
    }
}
