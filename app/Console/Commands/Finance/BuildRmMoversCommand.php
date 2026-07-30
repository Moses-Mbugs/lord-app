<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Services\Reports\RmMoversService;
use Illuminate\Console\Command;

class BuildRmMoversCommand extends Command
{
    protected $signature = 'reports:build-rm-movers
        {start : Start date YYYY-MM-DD}
        {end : End date YYYY-MM-DD}
    ';

    protected $description = 'Build rm_movers from customer_balances + customer_accounts_imports grouped by RM (acc_ofcr).';

    public function handle(RmMoversService $service): int
    {
        $start = (string) $this->argument('start');
        $end   = (string) $this->argument('end');

        try {
            $this->info("Building RM movers for {$start} → {$end}...");

            $count = $service->build($start, $end);

            $this->info("Done. {$count} RM rows inserted for {$start} → {$end}.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("RM movers build failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
