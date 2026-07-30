<?php


// Not in use


namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\SubSegmentMover;
use App\Services\Reports\SubSegmentMoversService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubSegmentMovementController extends Controller
{
    public function index()
    {
        return view('finance.sub_segment_movement.index');
    }

    public function build(Request $request, SubSegmentMoversService $service): JsonResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $inserted = $service->build($data['start_date'], $data['end_date']);

        return response()->json([
            'success' => true,
            'message' => "Sub-segment movement built successfully. Rows inserted: {$inserted}",
            'inserted' => $inserted,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'business' => ['nullable', 'string', 'max:255'],
            'business_segment_name' => ['nullable', 'string', 'max:255'],
            'mis_code' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $baseQuery = SubSegmentMover::query()
            ->whereDate('start_date', $data['start_date'])
            ->whereDate('end_date', $data['end_date']);

        $query = clone $baseQuery;

        if (! empty($data['business'])) {
            $query->where('business', $data['business']);
        }

        if (! empty($data['business_segment_name'])) {
            $query->where('business_segment_name', $data['business_segment_name']);
        }

        if (! empty($data['mis_code'])) {
            $query->where('mis_code', 'like', '%' . $data['mis_code'] . '%');
        }

        if (! empty($data['search'])) {
            $search = $data['search'];

            $query->where(function ($q) use ($search) {
                $q->where('mis_code', 'like', "%{$search}%")
                    ->orWhere('code_desc', 'like', "%{$search}%")
                    ->orWhere('business', 'like', "%{$search}%")
                    ->orWhere('business_segment_name', 'like', "%{$search}%")
                    ->orWhere('business_seg_short', 'like', "%{$search}%");
            });
        }

        $rows = $query
            ->orderByDesc(DB::raw('ABS(movement)'))
            ->get();

        $summary = [
            'start_balance' => round((float) $rows->sum('start_balance'), 2),
            'end_balance' => round((float) $rows->sum('end_balance'), 2),
            'movement' => round((float) $rows->sum('movement'), 2),
            'cif_count' => (int) $rows->sum('cif_count'),
            'biggest_positive_mover' => optional($rows->sortByDesc('movement')->first())->mis_code,
            'biggest_negative_mover' => optional($rows->sortBy('movement')->first())->mis_code,
            'rows_count' => $rows->count(),
            'last_build_at' => optional($rows->max('updated_at'))?->toDateTimeString(),
        ];

        $chartRows = $rows
            ->sortByDesc('movement')
            ->take(15)
            ->values()
            ->map(function ($row) {
                return [
                    'name' => $row->mis_code,
                    'label' => $row->mis_code . ' - ' . $row->code_desc,
                    'y' => (float) $row->movement,
                ];
            });

        $filters = [
            'businesses' => (clone $baseQuery)
                ->select('business')
                ->distinct()
                ->orderBy('business')
                ->pluck('business')
                ->filter()
                ->values(),
            'segments' => (clone $baseQuery)
                ->select('business_segment_name')
                ->distinct()
                ->orderBy('business_segment_name')
                ->pluck('business_segment_name')
                ->filter()
                ->values(),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'chart' => $chartRows,
            'rows' => $rows,
            'filters' => $filters,
        ]);
    }

    public function drilldown(Request $request, SubSegmentMoversService $service): JsonResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'mis_code' => ['required', 'string', 'max:255'],
        ]);

        $rows = $service->drilldown($data['start_date'], $data['end_date'], $data['mis_code']);

        return response()->json([
            'success' => true,
            'rows' => $rows,
        ]);
    }

    public function cifDrivers(
        Request $request,
        string $segment,
        SubSegmentMoversService $subSegmentMoversService
    ): JsonResponse {
        $config = $this->resolveSegment($segment);
        abort_unless($config !== null, 404);

        $groupKey = strtoupper(trim((string) $request->query('group_key')));
        $period = strtolower(trim((string) $request->query('period', 'daily')));
        $limit = (int) $request->query('limit', 20);

        $limit = max(5, min($limit, 100));

        if (!in_array($period, ['daily', 'mtd', 'ytd'], true)) {
            return response()->json([
                'message' => 'Invalid period selected.',
            ], 422);
        }

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
            'gainers' => $drivers['gainers'],
            'losers' => $drivers['losers'],
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
}
