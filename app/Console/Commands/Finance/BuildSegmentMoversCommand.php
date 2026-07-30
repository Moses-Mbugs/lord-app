<?php

namespace App\Console\Commands\Finance;

use App\Services\Reports\SegmentMoversService;
use Illuminate\Console\Command;

class BuildSegmentMoversCommand extends Command
{
    protected $signature = 'reports:build-segment-movers
        {start : Start date YYYY-MM-DD}
        {end : End date YYYY-MM-DD}
    ';

    protected $description = 'Build segment_movers (CB/CM/CS) from customer_balances + customer_accounts_imports (CIF-only LCY equivalent).';

    public function handle(SegmentMoversService $service): int
    {
        $start = (string) $this->argument('start');
        $end   = (string) $this->argument('end');

        try {
            $service->build($start, $end);

            $this->info("Segment movers built successfully for {$start} → {$end}.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Segment movers build failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
