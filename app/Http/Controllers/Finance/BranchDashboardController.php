<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Reports\BranchDailyPerformanceSummaryService;
use Illuminate\View\View;

class BranchDashboardController extends Controller
{
    public function __construct(private readonly BranchDailyPerformanceSummaryService $service)
    {
    }

    public function index(): View
    {
        $asOfDate = $this->service->latestBalanceDate() ?? now()->toDateString();
        $loanAsOfDate = $this->service->latestLoanAsOfDate() ?? $asOfDate;

        // Reads persisted branch_daily_performance_summaries; builds + persists on first
        // run for a given date so the dashboard never depends on an app-level cache.
        $branches = $this->service->findOrBuild($asOfDate, $loanAsOfDate);

        $topCustomersByBranch = $this->service->topCustomersByBranch($asOfDate, 10);
        $topLoanCustomersByBranch = $this->service->topLoanCustomersByBranch($loanAsOfDate, 10);
        foreach ($branches as &$branch) {
            $branch['top_customers'] = $topCustomersByBranch[$branch['code']] ?? [];
            $branch['top_loan_customers'] = $topLoanCustomersByBranch[$branch['code']] ?? [];
        }
        unset($branch);

        $targetTotals = [
            'deposits' => array_sum(array_column(BranchDailyPerformanceSummaryService::TARGETS_2026, 'deposits')),
            'accounts' => array_sum(array_column(BranchDailyPerformanceSummaryService::TARGETS_2026, 'accounts')),
            'lending'  => array_sum(array_column(BranchDailyPerformanceSummaryService::TARGETS_2026, 'lending')),
        ];

        return view('finance.branch-dashboard.index', [
            'branches'            => $branches,
            'asOfDate'            => $asOfDate,
            'loanAsOfDate'        => $loanAsOfDate,
            'totalActualDeposits' => array_sum(array_column($branches, 'actual_deposits')),
            'totalActualAccounts' => array_sum(array_column($branches, 'actual_accounts')),
            'totalActualLending'  => array_sum(array_column($branches, 'actual_lending')),
            'targetTotals'        => $targetTotals,
            'mtdDate'             => $branches[0]['mtd_reference_date'] ?? null,
            'ytdDate'             => $branches[0]['ytd_reference_date'] ?? null,
            'mtdLoanDate'         => $branches[0]['mtd_loan_reference_date'] ?? null,
            'ytdLoanDate'         => $branches[0]['ytd_loan_reference_date'] ?? null,
        ]);
    }
}
