<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BranchMoversController extends Controller
{
    public function index(): View
    {
        $periods = DB::table('group_movers')
            ->where('group_type', 'BRANCH')
            ->where('scope', 'SUMMARY')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->select('start_date', 'end_date')
            ->distinct()
            ->orderByDesc('end_date')
            ->orderByDesc('start_date')
            ->get();

        $latest = $periods->first();

        return view('finance.branch-movers.dashboard', [
            'periods'      => $periods,
            'defaultStart' => $latest?->start_date
                ? Carbon::parse($latest->start_date)->toDateString()
                : now()->subDay()->toDateString(),
            'defaultEnd'   => $latest?->end_date
                ? Carbon::parse($latest->end_date)->toDateString()
                : now()->toDateString(),
        ]);
    }

    public function kpis(Request $request): JsonResponse
    {
        [$start, $end] = $this->parseDates($request);
        $branch = $this->normalizeBranch($request->input('branch'));

        if ($branch && $branch !== 'ALL') {
            $row = DB::table('group_movers')
                ->where('group_type', 'BRANCH')
                ->where('scope', 'SUMMARY')
                ->where('group_key', $branch)
                ->whereDate('start_date', $start)
                ->whereDate('end_date', $end)
                ->first(['group_key', 'group_name', 'start_balance', 'end_balance', 'movement']);

            $gainCount = DB::table('branch_cif_movers')
                ->whereDate('start_date', $start)
                ->whereDate('end_date', $end)
                ->where('branch_code', $branch)
                ->where('direction', 'GAIN')
                ->count();

            $lossCount = DB::table('branch_cif_movers')
                ->whereDate('start_date', $start)
                ->whereDate('end_date', $end)
                ->where('branch_code', $branch)
                ->where('direction', 'LOSS')
                ->count();

            $topGainer = DB::table('branch_cif_movers')
                ->whereDate('start_date', $start)
                ->whereDate('end_date', $end)
                ->where('branch_code', $branch)
                ->where('direction', 'GAIN')
                ->orderBy('rank')
                ->first(['customer_name', 'movement']);

            $topLoser = DB::table('branch_cif_movers')
                ->whereDate('start_date', $start)
                ->whereDate('end_date', $end)
                ->where('branch_code', $branch)
                ->where('direction', 'LOSS')
                ->orderBy('rank')
                ->first(['customer_name', 'movement']);

            return response()->json([
                'mode'          => 'branch',
                'focus_key'     => $row->group_key ?? $branch,
                'focus_name'    => $row->group_name ?? $branch,
                'start_balance' => (float) ($row->start_balance ?? 0),
                'end_balance'   => (float) ($row->end_balance ?? 0),
                'movement'      => (float) ($row->movement ?? 0),
                'gain_count'    => $gainCount,
                'loss_count'    => $lossCount,
                'top_gainer'    => $topGainer
                    ? ['name' => $topGainer->customer_name, 'movement' => (float) $topGainer->movement]
                    : null,
                'top_loser'     => $topLoser
                    ? ['name' => $topLoser->customer_name, 'movement' => (float) $topLoser->movement]
                    : null,
            ]);
        }

        $total = DB::table('group_movers')
            ->where('group_type', 'BRANCH')
            ->where('scope', 'SUMMARY')
            ->where('group_key', 'ALL')
            ->whereDate('start_date', $start)
            ->whereDate('end_date', $end)
            ->first(['start_balance', 'end_balance', 'movement']);

        $gainCount = DB::table('group_movers')
            ->where('group_type', 'BRANCH')
            ->where('scope', 'SUMMARY')
            ->whereDate('start_date', $start)
            ->whereDate('end_date', $end)
            ->where('group_key', '<>', 'ALL')
            ->where('movement', '>', 0)
            ->count();

        $lossCount = DB::table('group_movers')
            ->where('group_type', 'BRANCH')
            ->where('scope', 'SUMMARY')
            ->whereDate('start_date', $start)
            ->whereDate('end_date', $end)
            ->where('group_key', '<>', 'ALL')
            ->where('movement', '<', 0)
            ->count();

        $topGainer = DB::table('group_movers')
            ->where('group_type', 'BRANCH')
            ->where('scope', 'TOP')
            ->where('direction', 'GAIN')
            ->whereDate('start_date', $start)
            ->whereDate('end_date', $end)
            ->orderBy('rank')
            ->first(['group_name', 'movement']);

        $topLoser = DB::table('group_movers')
            ->where('group_type', 'BRANCH')
            ->where('scope', 'TOP')
            ->where('direction', 'LOSS')
            ->whereDate('start_date', $start)
            ->whereDate('end_date', $end)
            ->orderBy('rank')
            ->first(['group_name', 'movement']);

        return response()->json([
            'mode'          => 'all',
            'focus_key'     => 'ALL',
            'focus_name'    => 'All Branches',
            'start_balance' => (float) ($total->start_balance ?? 0),
            'end_balance'   => (float) ($total->end_balance ?? 0),
            'movement'      => (float) ($total->movement ?? 0),
            'gain_count'    => $gainCount,
            'loss_count'    => $lossCount,
            'top_gainer'    => $topGainer
                ? ['name' => $topGainer->group_name, 'movement' => (float) $topGainer->movement]
                : null,
            'top_loser'     => $topLoser
                ? ['name' => $topLoser->group_name, 'movement' => (float) $topLoser->movement]
                : null,
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        [$start, $end] = $this->parseDates($request);
        $branch = $this->normalizeBranch($request->input('branch'));

        $query = DB::table('group_movers')
            ->where('group_type', 'BRANCH')
            ->where('scope', 'SUMMARY')
            ->whereDate('start_date', $start)
            ->whereDate('end_date', $end);

        if ($branch && $branch !== 'ALL') {
            $query->where('group_key', $branch);
        }

        $rows = $query
            ->orderByRaw("CASE WHEN group_key = 'ALL' THEN 99 ELSE 0 END")
            ->orderBy('group_key')
            ->get(['group_key', 'group_name', 'start_balance', 'end_balance', 'movement']);

        if ($branch && $branch !== 'ALL') {
            $rows = $rows->reject(fn($row) => $row->group_key === 'ALL')->values();
        }

        return response()->json($rows);
    }

    public function topMovers(Request $request): JsonResponse
    {
        [$start, $end] = $this->parseDates($request);
        $branch = $this->normalizeBranch($request->input('branch'));
        $limit = min(20, max(1, (int) $request->input('limit', 10)));

        if ($branch && $branch !== 'ALL') {
            $gainers = DB::table('branch_cif_movers')
                ->whereDate('start_date', $start)
                ->whereDate('end_date', $end)
                ->where('branch_code', $branch)
                ->where('direction', 'GAIN')
                ->orderBy('rank')
                ->limit($limit)
                ->get([
                    'rank',
                    'branch_code',
                    'branch_name',
                    'cif',
                    'customer_name',
                    'start_balance',
                    'end_balance',
                    'movement',
                ]);

            $losers = DB::table('branch_cif_movers')
                ->whereDate('start_date', $start)
                ->whereDate('end_date', $end)
                ->where('branch_code', $branch)
                ->where('direction', 'LOSS')
                ->orderBy('rank')
                ->limit($limit)
                ->get([
                    'rank',
                    'branch_code',
                    'branch_name',
                    'cif',
                    'customer_name',
                    'start_balance',
                    'end_balance',
                    'movement',
                ]);

            return response()->json([
                'mode'    => 'branch',
                'gainers' => $gainers,
                'losers'  => $losers,
            ]);
        }

        $gainers = DB::table('group_movers')
            ->where('group_type', 'BRANCH')
            ->where('scope', 'TOP')
            ->where('direction', 'GAIN')
            ->whereDate('start_date', $start)
            ->whereDate('end_date', $end)
            ->orderBy('rank')
            ->limit($limit)
            ->get(['rank', 'group_key', 'group_name', 'start_balance', 'end_balance', 'movement']);

        $losers = DB::table('group_movers')
            ->where('group_type', 'BRANCH')
            ->where('scope', 'TOP')
            ->where('direction', 'LOSS')
            ->whereDate('start_date', $start)
            ->whereDate('end_date', $end)
            ->orderBy('rank')
            ->limit($limit)
            ->get(['rank', 'group_key', 'group_name', 'start_balance', 'end_balance', 'movement']);

        return response()->json([
            'mode'    => 'all',
            'gainers' => $gainers,
            'losers'  => $losers,
        ]);
    }

    public function cifMovers(Request $request): JsonResponse
    {
        [$start, $end] = $this->parseDates($request);
        $branch = $this->normalizeBranch($request->input('branch'));
        $direction = strtoupper((string) $request->input('direction', ''));
        $limit = min(50, max(1, (int) $request->input('limit', 20)));

        $query = DB::table('branch_cif_movers')
            ->whereDate('start_date', $start)
            ->whereDate('end_date', $end);

        if ($branch && $branch !== 'ALL') {
            $query->where('branch_code', $branch);
        }

        if (in_array($direction, ['GAIN', 'LOSS'], true)) {
            $query->where('direction', $direction);
        }

        $rows = $query
            ->orderBy('branch_code')
            ->orderBy('direction')
            ->orderBy('rank')
            ->limit($limit)
            ->get([
                'rank',
                'branch_code',
                'branch_name',
                'cif',
                'customer_name',
                'start_balance',
                'end_balance',
                'movement',
                'direction',
            ]);

        return response()->json($rows);
    }

    public function movementChart(Request $request): JsonResponse
    {
        [$start, $end] = $this->parseDates($request);
        $branch = $this->normalizeBranch($request->input('branch'));

        $query = DB::table('group_movers')
            ->where('group_type', 'BRANCH')
            ->where('scope', 'SUMMARY')
            ->whereDate('start_date', $start)
            ->whereDate('end_date', $end)
            ->where('group_key', '<>', 'ALL');

        if ($branch && $branch !== 'ALL') {
            $query->where('group_key', $branch);
        }

        $rows = $query
            ->orderByDesc('movement')
            ->get(['group_key', 'group_name', 'movement']);

        $labels = [];
        $movements = [];
        $colors = [];

        foreach ($rows as $row) {
            $labels[] = preg_replace('/^P\d+-/', '', (string) $row->group_name);
            $movements[] = round((float) $row->movement, 2);
            $colors[] = (float) $row->movement >= 0 ? '#BED600' : '#0082BB';
        }

        return response()->json([
            'labels'    => $labels,
            'movements' => $movements,
            'colors'    => $colors,
        ]);
    }

    public function trendSummary(Request $request): JsonResponse
    {
        [$start, $end] = $this->parseDates($request);
        $branch = $this->normalizeBranch($request->input('branch'));
        $mode = strtolower((string) $request->input('mode', 'daily'));
        $mode = in_array($mode, ['daily', 'weekly', 'monthly'], true) ? $mode : 'daily';

        $targetKey = $branch && $branch !== 'ALL' ? $branch : 'ALL';

        $rows = DB::table('group_movers')
            ->where('group_type', 'BRANCH')
            ->where('scope', 'SUMMARY')
            ->where('group_key', $targetKey)
            ->whereBetween('end_date', [$start, $end])
            ->orderBy('end_date')
            ->get(['start_date', 'end_date', 'start_balance', 'end_balance', 'movement']);

        $series = $this->bucketTrendRows($rows, $mode);

        return response()->json([
            'mode'             => $mode,
            'labels'           => $series->pluck('label')->values(),
            'movements'        => $series->pluck('movement')->values(),
            'closing_balances' => $series->pluck('closing_balance')->values(),
        ]);
    }

    public function branches(Request $request): JsonResponse
    {
        [$start, $end] = $this->parseDates($request);

        $rows = DB::table('group_movers')
            ->where('group_type', 'BRANCH')
            ->where('scope', 'SUMMARY')
            ->whereDate('start_date', $start)
            ->whereDate('end_date', $end)
            ->where('group_key', '<>', 'ALL')
            ->orderBy('group_key')
            ->get(['group_key', 'group_name']);

        return response()->json($rows);
    }

    private function bucketTrendRows(Collection $rows, string $mode): Collection
    {
        if ($rows->isEmpty()) {
            return collect();
        }

        return $rows
            ->groupBy(function ($row) use ($mode) {
                $end = Carbon::parse($row->end_date);

                return match ($mode) {
                    'weekly'  => $end->copy()->startOfWeek()->toDateString(),
                    'monthly' => $end->format('Y-m'),
                    default   => $end->toDateString(),
                };
            })
            ->map(function (Collection $bucket, string $key) use ($mode) {
                $ordered = $bucket->sortBy('end_date')->values();
                $first = $ordered->first();
                $last = $ordered->last();

                if ($mode === 'weekly') {
                    $startLabel = Carbon::parse($key)->format('d M');
                    $endLabel = Carbon::parse($key)->endOfWeek()->format('d M');
                    $label = $startLabel . ' - ' . $endLabel;
                } elseif ($mode === 'monthly') {
                    $label = Carbon::parse($key . '-01')->format('M Y');
                } else {
                    $label = Carbon::parse($key)->format('d M');
                }

                return [
                    'label'           => $label,
                    'movement'        => round((float) $bucket->sum('movement'), 2),
                    'closing_balance' => round((float) ($last->end_balance ?? 0), 2),
                    'opening_balance' => round((float) ($first->start_balance ?? 0), 2),
                ];
            })
            ->values();
    }

    private function normalizeBranch(mixed $branch): ?string
    {
        if ($branch === null) {
            return null;
        }

        $branch = strtoupper(trim((string) $branch));

        return $branch === '' ? null : $branch;
    }

    private function parseDates(Request $request): array
    {
        if (!$request->filled('start') || !$request->filled('end')) {
            $latest = DB::table('group_movers')
                ->where('group_type', 'BRANCH')
                ->where('scope', 'SUMMARY')
                ->orderByDesc('end_date')
                ->orderByDesc('start_date')
                ->first(['start_date', 'end_date']);

            $fallbackStart = $latest?->start_date
                ? Carbon::parse($latest->start_date)->toDateString()
                : now()->subDay()->toDateString();

            $fallbackEnd = $latest?->end_date
                ? Carbon::parse($latest->end_date)->toDateString()
                : now()->toDateString();

            return [$fallbackStart, $fallbackEnd];
        }

        $start = Carbon::parse((string) $request->input('start'))->toDateString();
        $end = Carbon::parse((string) $request->input('end'))->toDateString();

        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }
}
