<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\RelationshipManager;
use App\Services\Finance\RmPerformanceService;
use App\Services\Reports\BranchDailyPerformanceSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RmPerformanceController extends Controller
{
    public function __construct(private readonly RmPerformanceService $service)
    {
    }

    public function index(Request $request): View
    {
        $year = (int) $request->input('year', now()->year);

        $performance = $this->service->forYear($year);
        $branchByRm  = $this->service->primaryBranchByRm();
        $rms         = RelationshipManager::query()->orderBy('rm_code')->get();

        $rows = $rms->map(function (RelationshipManager $rm) use ($performance, $branchByRm) {
            $code       = strtoupper(trim($rm->rm_code));
            $p          = $performance[$code] ?? null;
            $branchCode = $branchByRm[$code] ?? null;

            return [
                'rm_code'                 => $rm->rm_code,
                'name'                    => $rm->name,
                'segment'                 => $rm->segment,
                'subsegment'              => $rm->subsegment,
                'branch_code'             => $branchCode,
                'branch_name'             => $branchCode
                    ? (BranchDailyPerformanceSummaryService::TARGETS_2026[$branchCode]['name'] ?? $branchCode)
                    : null,
                'latest_month'            => $p['latest_month'] ?? null,
                'month_deposit_movement'  => $p['month_deposit_movement'] ?? null,
                'deposit_portfolio'       => (float) ($p['deposit_portfolio'] ?? 0.0),
                'month_loan_disbursed'    => (float) ($p['month_loan_disbursed'] ?? 0.0),
                'month_ntb'               => (int) ($p['month_ntb'] ?? 0),
                'ytd_deposit_movement'    => (float) ($p['ytd_deposit_movement'] ?? 0.0),
                'ytd_loan_disbursed'      => (float) ($p['ytd_loan_disbursed'] ?? 0.0),
                'ytd_ntb'                 => (int) ($p['ytd_ntb'] ?? 0),
                'balance_snapshot_date'   => $p['balance_snapshot_date'] ?? null,
                'loan_snapshot_date'      => $p['loan_snapshot_date'] ?? null,
                'has_data'                => $p !== null,
            ];
        })->sortByDesc('ytd_deposit_movement')->values();

        $rows = $this->applyBranchRanks($rows);

        $tracked = $rows->where('has_data', true);

        $latestMonth = $tracked->max('latest_month');

        $totals = [
            'deposit_movement'  => (float) $tracked->sum('ytd_deposit_movement'),
            'deposit_portfolio' => (float) $tracked->sum('deposit_portfolio'),
            'loan_disbursed'    => (float) $tracked->sum('ytd_loan_disbursed'),
            'ntb'               => (int) $tracked->sum('ytd_ntb'),
        ];

        $latestSnapshotDates = [
            'balance' => DB::table('rm_performance_monthly')->where('period_year', $year)->max('balance_snapshot_date'),
            'loan'    => DB::table('rm_performance_monthly')->where('period_year', $year)->max('loan_snapshot_date'),
        ];

        return view('finance.rm-performance.dashboard', [
            'rows'            => $rows,
            'year'            => $year,
            'totals'          => $totals,
            'trackedCount'    => $tracked->count(),
            'untrackedCount'  => $rows->count() - $tracked->count(),
            'latestMonth'     => $latestMonth,
            'snapshotDates'   => $latestSnapshotDates,
        ]);
    }

    /**
     * Ranks each RM against peers in the same branch (not bank-wide — a 3-person branch
     * shouldn't be judged against Towers), separately for each of the three YTD metrics.
     * Only RMs with data for the year are ranked; unassigned-branch RMs rank among each
     * other rather than being dropped. Rank 1 = best (highest YTD figure) within the branch.
     *
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $rows
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function applyBranchRanks($rows)
    {
        $rankFields = [
            'ytd_deposit_movement' => 'deposit',
            'ytd_loan_disbursed'   => 'loan',
            'ytd_ntb'              => 'ntb',
        ];

        $ranksByRmCode = [];

        $rows->where('has_data', true)
            ->groupBy(fn ($row) => $row['branch_code'] ?? '__unassigned__')
            ->each(function ($branchRows) use ($rankFields, &$ranksByRmCode) {
                $total = $branchRows->count();

                foreach ($rankFields as $field => $prefix) {
                    $branchRows->sortByDesc($field)->values()->each(function ($row, $index) use ($prefix, $total, &$ranksByRmCode) {
                        $ranksByRmCode[$row['rm_code']]["{$prefix}_rank"]       = $index + 1;
                        $ranksByRmCode[$row['rm_code']]["{$prefix}_rank_total"] = $total;
                    });
                }
            });

        return $rows->map(function ($row) use ($ranksByRmCode) {
            $ranks = $ranksByRmCode[$row['rm_code']] ?? [];

            return array_merge($row, [
                'deposit_rank'       => $ranks['deposit_rank'] ?? null,
                'deposit_rank_total' => $ranks['deposit_rank_total'] ?? null,
                'loan_rank'          => $ranks['loan_rank'] ?? null,
                'loan_rank_total'    => $ranks['loan_rank_total'] ?? null,
                'ntb_rank'           => $ranks['ntb_rank'] ?? null,
                'ntb_rank_total'     => $ranks['ntb_rank_total'] ?? null,
            ]);
        });
    }

    public function trend(Request $request): JsonResponse
    {
        $data = $request->validate([
            'rm_code' => ['required', 'string', 'max:100'],
            'months'  => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);

        $rmCode = strtoupper(trim($data['rm_code']));
        $months = (int) ($data['months'] ?? 24);

        $rm = RelationshipManager::query()->where('rm_code', $rmCode)->first();

        return response()->json([
            'success' => true,
            'rm_code' => $rmCode,
            'rm_name' => $rm?->name,
            'segment' => $rm?->segment,
            'series'  => $this->service->trendForRm($rmCode, $months),
        ]);
    }

    public function build(): JsonResponse
    {
        $result = $this->service->build();

        return response()->json([
            'success' => true,
            'message' => "RM performance rebuilt successfully. Rows written: {$result['rows']}"
                . ($result['unparsed_value_dt'] > 0 ? " ({$result['unparsed_value_dt']} loan row(s) had an unparseable value date and were skipped)." : '.'),
            'rows'    => $result['rows'],
        ]);
    }
}
