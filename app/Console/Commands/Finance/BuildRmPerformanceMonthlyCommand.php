<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Services\Finance\RmPerformanceService;
use Illuminate\Console\Command;

/**
 * Rebuilds rm_performance_monthly (deposits mobilized, loans disbursed proxy, NTB
 * accounts opened — per RM, per calendar month) from customer_balances, loan_listings
 * and customer_accounts_imports. Not scheduled — run manually after an import, or via
 * the "Rebuild" button on /finance/rm-performance, same on-demand convention as
 * finance:build-rm-workload.
 */
class BuildRmPerformanceMonthlyCommand extends Command
{
    protected $signature = 'finance:build-rm-performance-monthly';

    protected $description = 'Rebuilds rm_performance_monthly from customer_balances, loan_listings and customer_accounts_imports.';

    public function handle(RmPerformanceService $service): int
    {
        $this->info('Building RM performance monthly summary...');

        $result = $service->build();

        $this->info("Rebuilt rm_performance_monthly: {$result['rows']} row(s).");

        if ($result['unparsed_value_dt'] > 0) {
            $this->warn("{$result['unparsed_value_dt']} loan_listings row(s) had an unparseable value_dt and were excluded from the loans-disbursed figures.");
        }

        return self::SUCCESS;
    }
}
