<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Services\Reports\BranchDailyPerformanceSummaryService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class BuildBranchDailySummaryCommand extends Command
{
    protected $signature = 'finance:build-branch-daily-summary
        {date? : Balance date YYYY-MM-DD. Defaults to latest customer_balances date}
        {--loan-date= : Loan as-at date YYYY-MM-DD. Defaults to latest loan_listings date}
        {--fresh : Force rebuild even if summaries already exist for the date}';

    protected $description = 'Build and store per-branch daily performance summaries (deposits, accounts, lending, LDR, currency/deposit mix, MTD/YTD) for the branch dashboard.';

    public function __construct(private readonly BranchDailyPerformanceSummaryService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $dateArg = trim((string) ($this->argument('date') ?? ''));
            $balanceDate = $dateArg !== ''
                ? Carbon::parse($dateArg)->toDateString()
                : $this->service->latestBalanceDate();

            if (!$balanceDate) {
                $this->error('No balance_date found in customer_balances.');
                return self::FAILURE;
            }

            $loanDateOpt = trim((string) ($this->option('loan-date') ?? ''));
            $loanAsOfDate = $loanDateOpt !== ''
                ? Carbon::parse($loanDateOpt)->toDateString()
                : ($this->service->latestLoanAsOfDate() ?? $balanceDate);

            $this->info("Building branch daily performance summaries for {$balanceDate} (loans as of {$loanAsOfDate})...");

            $rows = (bool) $this->option('fresh')
                ? $this->service->buildForDate($balanceDate, $loanAsOfDate)
                : $this->service->findOrBuild($balanceDate, $loanAsOfDate);

            $this->line('');
            $this->info('Branch Daily Performance Summary');

            foreach ($rows as $row) {
                $this->line(sprintf(
                    '  [%s] %-20s Deposits %6.1f%%  Accounts %6.1f%%  Lending %6.1f%%  LDR %6.1f%%',
                    $row['code'],
                    $row['name'],
                    $row['deposit_pct'],
                    $row['account_pct'],
                    $row['lending_pct'],
                    $row['ldr_pct']
                ));
            }

            $this->line('');
            $this->info('Done. ' . count($rows) . ' branches built.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Build failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
