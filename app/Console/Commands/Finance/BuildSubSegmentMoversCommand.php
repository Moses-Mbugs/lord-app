<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Services\Reports\SubSegmentMoversService;
use Illuminate\Console\Command;

class BuildSubSegmentMoversCommand extends Command
{
    protected $signature = 'reports:build-sub-segment-movers
        {start : Start date YYYY-MM-DD}
        {end : End date YYYY-MM-DD}
    ';

    protected $description = 'Build sub_segment_movers from customer_balances + customer_accounts_imports (CIF-only LCY equivalent).';

    public function handle(SubSegmentMoversService $service): int
    {
        $start = (string) $this->argument('start');
        $end   = (string) $this->argument('end');

        try {
            $this->info("Building sub-segment movers for {$start} → {$end}...");

            $count = $service->build($start, $end);

            $this->info("Done. {$count} sub-segment rows inserted for {$start} → {$end}.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Sub-segment movers build failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
