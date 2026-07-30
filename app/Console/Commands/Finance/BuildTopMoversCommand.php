<?php

namespace App\Console\Commands\Finance;

use App\Services\Reports\TopMoversService;
use Illuminate\Console\Command;

class BuildTopMoversCommand extends Command
{
    protected $signature = 'reports:build-top-movers
        {start : Start date YYYY-MM-DD}
        {end : End date YYYY-MM-DD}
        {currencyType=LCY : LCY or FCY (ignored when --scope=cif_only)}
        {--limit=20 : How many movers per bucket}
        {--scope=cif_currency : cif_currency or cif_only}
    ';

    protected $description = 'Build top_movers from customer_balances (supports per-currency and CIF-only).';

    public function handle(TopMoversService $service): int
    {
        $start = (string) $this->argument('start');
        $end = (string) $this->argument('end');
        $currencyType = (string) $this->argument('currencyType');
        $limit = (int) $this->option('limit');
        $scope = (string) $this->option('scope');

        try {
            $service->build($start, $end, $currencyType, $limit, $scope);

            $this->info("Top movers built successfully (scope={$scope}) for {$currencyType} ({$start} → {$end}).");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Top movers build failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
