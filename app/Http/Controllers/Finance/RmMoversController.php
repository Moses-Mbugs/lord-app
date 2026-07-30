<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\RmMover;
use App\Services\Reports\RmMoversService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class RmMoversController extends Controller
{

    public function index(): View
    {
        $periods = RmMover::query()
            ->select('start_date', 'end_date')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->distinct()
            ->orderByDesc('end_date')
            ->orderByDesc('start_date')
            ->get();

        [$defaultStart, $defaultEnd] = $this->getLatestAvailablePeriod();

        return view('finance.rm-movers.dashboard', [
            'periods'      => $periods,
            'defaultStart' => $defaultStart,
            'defaultEnd'   => $defaultEnd,
        ]);
    }
    public function build(Request $request, RmMoversService $service): JsonResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        try {
            $inserted = $service->build($data['start_date'], $data['end_date']);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success'  => true,
            'message'  => "RM movers built successfully. Rows inserted: {$inserted}",
            'inserted' => $inserted,
        ]);
    }

    public function kpis(Request $request): JsonResponse
    {
        [$start, $end] = $this->parseDates($request);
        $rmCode = $this->normalizeRm($request->input('rm_code'));

        if ($rmCode) {
            $row = RmMover::query()
                ->whereDate('start_date', $start)
                ->whereDate('end_date', $end)
                ->where('rm_code', $rmCode)
                ->first();

            return response()->json([
                'success'       => true,
                'mode'          => 'rm',
                'focus_key'     => $rmCode,
                'start_balance' => round((float) ($row->start_balance ?? 0), 2),
                'end_balance'   => round((float) ($row->end_balance ?? 0), 2),
                'movement'      => round((float) ($row->movement ?? 0), 2),
                'cif_count'     => (int) ($row->cif_count ?? 0),
                'gain_count'    => $row && (float) $row->movement > 0 ? 1 : 0,
                'loss_count'    => $row && (float) $row->movement < 0 ? 1 : 0,
                'flat_count'    => $row && (float) $row->movement == 0.0 ? 1 : 0,
            ]);
        }

        $rows = RmMover::query()
            ->whereDate('start_date', $start)
            ->whereDate('end_date', $end)
            ->get();

        $gainCount = $rows->where('movement', '>', 0)->count();
        $lossCount = $rows->where('movement', '<', 0)->count();
        $flatCount = $rows->where('movement', '=', 0)->count();

        $topGainer = $rows->sortByDesc('movement')->first();
        $topLoser  = $rows->sortBy('movement')->first();

        return response()->json([
            'success'       => true,
            'mode'          => 'all',
            'focus_key'     => 'ALL',
            'start_balance' => round((float) $rows->sum('start_balance'), 2),
            'end_balance'   => round((float) $rows->sum('end_balance'), 2),
            'movement'      => round((float) $rows->sum('movement'), 2),
            'cif_count'     => (int) $rows->sum('cif_count'),
            'rm_count'      => $rows->count(),
            'gain_count'    => $gainCount,
            'loss_count'    => $lossCount,
            'flat_count'    => $flatCount,
            'top_gainer'    => $topGainer
                ? [
                    'rm_code'  => $topGainer->rm_code,
                    'movement' => round((float) $topGainer->movement, 2),
                ]
                : null,
            'top_loser'     => $topLoser && (float) $topLoser->movement < 0
                ? [
                    'rm_code'  => $topLoser->rm_code,
                    'movement' => round((float) $topLoser->movement, 2),
                ]
                : null,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        [$start, $end] = $this->parseDates($request);

        $rmCode     = $this->normalizeRm($request->input('rm_code'));
        $segment    = $this->normalizeSegment($request->input('segment'));
        $subsegment = $this->normalizeSegment($request->input('subsegment'));
        $search     = trim((string) $request->input('search', ''));

        $query = RmMover::query()
            ->select('rm_movers.*', 'relationship_managers.name as rm_name', 'relationship_managers.segment as rm_segment', 'relationship_managers.subsegment as rm_subsegment')
            ->leftJoin('relationship_managers', 'relationship_managers.rm_code', '=', 'rm_movers.rm_code')
            ->whereDate('rm_movers.start_date', $start)
            ->whereDate('rm_movers.end_date', $end);

        if ($rmCode) {
            $query->where('rm_movers.rm_code', $rmCode);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('rm_movers.rm_code', 'like', "%{$search}%")
                    ->orWhere('relationship_managers.name', 'like', "%{$search}%");
            });
        }

        if ($segment) {
            $query->where('relationship_managers.segment', $segment);
        }

        if ($subsegment && Schema::hasColumn('relationship_managers', 'subsegment')) {
            $query->where('relationship_managers.subsegment', $subsegment);
        }

        $rows = $query
            ->orderByDesc(DB::raw('ABS(rm_movers.movement)'))
            ->get();

        return response()->json([
            'success' => true,
            'rows'    => $rows->values(),
            'summary' => [
                'start_balance' => round((float) $rows->sum('start_balance'), 2),
                'end_balance'   => round((float) $rows->sum('end_balance'), 2),
                'movement'      => round((float) $rows->sum('movement'), 2),
                'cif_count'     => (int) $rows->sum('cif_count'),
                'rm_count'      => $rows->count(),
                'gain_count'    => $rows->where('movement', '>', 0)->count(),
                'loss_count'    => $rows->where('movement', '<', 0)->count(),
                'flat_count'    => $rows->where('movement', '=', 0)->count(),
            ],
        ]);
    }

    public function topMovers(Request $request): JsonResponse
    {
        [$start, $end] = $this->parseDates($request);

        $rmCode = $this->normalizeRm($request->input('rm_code'));
        $limit  = min(20, max(1, (int) $request->input('limit', 10)));

        $rows = RmMover::query()
            ->whereDate('start_date', $start)
            ->whereDate('end_date', $end)
            ->when($rmCode, fn($query) => $query->where('rm_code', $rmCode))
            ->get();

        $gainers = $rows->where('movement', '>', 0)
            ->sortByDesc('movement')
            ->take($limit)
            ->map(fn($r) => [
                'rm_code'       => $r->rm_code,
                'start_balance' => round((float) $r->start_balance, 2),
                'end_balance'   => round((float) $r->end_balance, 2),
                'movement'      => round((float) $r->movement, 2),
                'cif_count'     => (int) $r->cif_count,
            ])
            ->values();

        $losers = $rows->where('movement', '<', 0)
            ->sortBy('movement')
            ->take($limit)
            ->map(fn($r) => [
                'rm_code'       => $r->rm_code,
                'start_balance' => round((float) $r->start_balance, 2),
                'end_balance'   => round((float) $r->end_balance, 2),
                'movement'      => round((float) $r->movement, 2),
                'cif_count'     => (int) $r->cif_count,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'gainers' => $gainers,
            'losers'  => $losers,
        ]);
    }

    public function drilldown(Request $request, RmMoversService $service): JsonResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'rm_code'    => ['required', 'string', 'max:100'],
            'limit'      => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $rmCode = strtoupper(trim($data['rm_code']));
        $limit  = (int) ($data['limit'] ?? 25);

        $rows = $service->drilldown(
            $data['start_date'],
            $data['end_date'],
            $rmCode,
            $limit
        );

        return response()->json([
            'success' => true,
            'rows'    => $rows,
            'meta'    => [
                'rm_code' => $rmCode,
                'limit'   => $limit,
                'count'   => count($rows),
            ],
        ]);
    }

    public function trend(Request $request): JsonResponse
    {
        [$start, $end] = $this->parseDates($request);

        $rmCode = $this->normalizeRm($request->input('rm_code'));

        $mode = strtolower((string) $request->input('mode', 'daily'));
        $mode = in_array($mode, ['daily', 'weekly', 'monthly'], true) ? $mode : 'daily';

        $query = RmMover::query()
            ->whereDate('end_date', '>=', $start)
            ->whereDate('end_date', '<=', $end)
            ->orderBy('end_date');

        if ($rmCode) {
            $query->where('rm_code', $rmCode);
        }

        $rows = $rmCode
            ? $query->get()
            : $query
            ->select(
                'start_date',
                'end_date',
                DB::raw('SUM(start_balance) AS start_balance'),
                DB::raw('SUM(end_balance) AS end_balance'),
                DB::raw('SUM(movement) AS movement')
            )
            ->groupBy('start_date', 'end_date')
            ->get();

        $series = $this->bucketTrendRows($rows, $mode);

        return response()->json([
            'success'          => true,
            'mode'             => $mode,
            'labels'           => $series->pluck('label')->values(),
            'movements'        => $series->pluck('movement')->values(),
            'closing_balances' => $series->pluck('closing_balance')->values(),
        ]);
    }

    public function rmList(Request $request): JsonResponse
    {
        [$start, $end] = $this->parseDates($request);
        $segment    = $this->normalizeSegment($request->input('segment'));
        $subsegment = $this->normalizeSegment($request->input('subsegment'));

        $query = RmMover::query()
            ->select('rm_movers.rm_code', 'relationship_managers.name as rm_name')
            ->leftJoin('relationship_managers', 'relationship_managers.rm_code', '=', 'rm_movers.rm_code')
            ->whereDate('rm_movers.start_date', $start)
            ->whereDate('rm_movers.end_date', $end)
            ->orderBy('rm_movers.rm_code');

        if ($segment) {
            $query->where('relationship_managers.segment', $segment);
        }

        if ($subsegment && Schema::hasColumn('relationship_managers', 'subsegment')) {
            $query->where('relationship_managers.subsegment', $subsegment);
        }

        $rms = $query
            ->get()
            ->filter(fn($r) => filled($r->rm_code))
            ->map(fn($r) => [
                'code' => $r->rm_code,
                'name' => $r->rm_name ?? '',
            ])
            ->values();

        return response()->json($rms);
    }

    public function segmentList(): JsonResponse
    {
        $segments = [];

        if (Schema::hasTable('relationship_managers') && Schema::hasColumn('relationship_managers', 'segment')) {
            $segments = DB::table('relationship_managers')
                ->select('segment')
                ->whereNotNull('segment')
                ->where('segment', '<>', '')
                ->distinct()
                ->orderBy('segment')
                ->pluck('segment')
                ->values()
                ->all();
        }

        return response()->json($segments);
    }

    public function subsegmentList(Request $request): JsonResponse
    {
        $segment    = $this->normalizeSegment($request->input('segment'));
        $subsegments = [];

        if (Schema::hasTable('relationship_managers') && Schema::hasColumn('relationship_managers', 'subsegment')) {
            $query = DB::table('relationship_managers')
                ->select('subsegment')
                ->whereNotNull('subsegment')
                ->where('subsegment', '<>', '');

            if ($segment) {
                $query->where('segment', $segment);
            }

            $subsegments = $query->distinct()->orderBy('subsegment')->pluck('subsegment')->values()->all();
        }

        return response()->json($subsegments);
    }

    public function singleRmStats(Request $request): JsonResponse
    {
        $data = $request->validate([
            'rm_code'    => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date'],
            'segment'    => ['nullable', 'string', 'max:100'],
            'subsegment' => ['nullable', 'string', 'max:100'],
        ]);

        $rmCode     = strtoupper(trim($data['rm_code']));
        $startDate  = Carbon::parse($data['start_date'])->toDateString();
        $endDate    = Carbon::parse($data['end_date'])->toDateString();
        $refDate    = Carbon::parse($endDate);
        $segment    = $this->normalizeSegment($data['segment'] ?? null);
        $subsegment = $this->normalizeSegment($data['subsegment'] ?? null);

        // Rank for the selected period — must respect the same segment/sub-segment
        // filter the visible table uses, otherwise "Rank #N" can disagree with the
        // RM's actual position in the (filtered) All RMs Summary table.
        $periodRow = RmMover::query()
            ->whereDate('start_date', $startDate)
            ->whereDate('end_date', $endDate)
            ->where('rm_code', $rmCode)
            ->first();

        $rank = null;
        if ($periodRow) {
            $absMovement = abs((float) $periodRow->movement);
            $rank        = RmMover::query()
                ->select('rm_movers.*')
                ->leftJoin('relationship_managers', 'relationship_managers.rm_code', '=', 'rm_movers.rm_code')
                ->whereDate('rm_movers.start_date', $startDate)
                ->whereDate('rm_movers.end_date', $endDate)
                ->whereRaw('ABS(rm_movers.movement) > ?', [$absMovement])
                ->when($segment, fn ($q) => $q->where('relationship_managers.segment', $segment))
                ->when(
                    $subsegment && Schema::hasColumn('relationship_managers', 'subsegment'),
                    fn ($q) => $q->where('relationship_managers.subsegment', $subsegment)
                )
                ->count() + 1;
        }

        // RM profile from relationship_managers
        $rm = DB::table('relationship_managers')->where('rm_code', $rmCode)->first();

        // Helper: find best snapshot for a standard period
        $findSnap = function (string $periodStart) use ($rmCode, $endDate) {
            $row = RmMover::query()
                ->where('rm_code', $rmCode)
                ->whereDate('start_date', $periodStart)
                ->whereDate('end_date', '<=', $endDate)
                ->orderByDesc('end_date')
                ->first();

            if (! $row) {
                return null;
            }

            return [
                'start_date'    => Carbon::parse($row->start_date)->toDateString(),
                'end_date'      => Carbon::parse($row->end_date)->toDateString(),
                'start_balance' => round((float) $row->start_balance, 2),
                'end_balance'   => round((float) $row->end_balance, 2),
                'movement'      => round((float) $row->movement, 2),
                'cif_count'     => (int) $row->cif_count,
            ];
        };

        return response()->json([
            'success' => true,
            'rm_code' => $rmCode,
            'rm_name' => $rm?->name ?? null,
            'segment' => $rm?->segment ?? null,
            'rank'    => $rank,
            'ytd'     => $findSnap($refDate->copy()->startOfYear()->toDateString()),
            'mtd'     => $findSnap($refDate->copy()->startOfMonth()->toDateString()),
            'wtd'     => $findSnap($refDate->copy()->startOfWeek(Carbon::MONDAY)->toDateString()),
        ]);
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
                $last = $bucket->sortBy('end_date')->last();

                $label = match ($mode) {
                    'weekly'  => Carbon::parse($key)->format('d M') . ' - ' . Carbon::parse($key)->endOfWeek()->format('d M'),
                    'monthly' => Carbon::parse($key . '-01')->format('M Y'),
                    default   => Carbon::parse($key)->format('d M'),
                };

                return [
                    'label'           => $label,
                    'movement'        => round((float) $bucket->sum('movement'), 2),
                    'closing_balance' => round((float) ($last->end_balance ?? 0), 2),
                ];
            })
            ->values();
    }

    private function normalizeRm(mixed $rm): ?string
    {
        if ($rm === null) {
            return null;
        }

        $rm = strtoupper(trim((string) $rm));

        return $rm === '' ? null : $rm;
    }

    private function normalizeSegment(mixed $segment): ?string
    {
        if ($segment === null) {
            return null;
        }

        $s = trim((string) $segment);

        return $s === '' ? null : $s;
    }

    private function parseDates(Request $request): array
    {
        if (! $request->filled('start') || ! $request->filled('end')) {
            return $this->getLatestAvailablePeriod();
        }
        $start = Carbon::parse((string) $request->input('start'))->toDateString();
        $end   = Carbon::parse((string) $request->input('end'))->toDateString();

        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }

    private function getLatestAvailablePeriod(): array
    {
        $latest = RmMover::query()
            ->select('start_date', 'end_date')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->orderByDesc('end_date')
            ->orderByDesc('start_date')
            ->first();

        return [
            $latest?->start_date
                ? Carbon::parse($latest->start_date)->toDateString()
                : now()->toDateString(),

            $latest?->end_date
                ? Carbon::parse($latest->end_date)->toDateString()
                : now()->toDateString(),
        ];
    }
}
