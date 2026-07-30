<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Services\Reports\GroupMoversService;
use Illuminate\Console\Command;
use Throwable;

class BuildBranchMoversCommand extends Command
{
    protected $signature = 'reports:build-branch-movers
        {start : Start date YYYY-MM-DD}
        {end : End date YYYY-MM-DD}
        {--limit=10 : How many top branches per bucket (gainers + losers)}
    ';

    protected $description = 'Build BRANCH movers into group_movers (summary + top gainers/losers). Excludes P50.';

    public function handle(GroupMoversService $service): int
    {
        $start = (string) $this->argument('start');
        $end   = (string) $this->argument('end');
        $limit = (int) $this->option('limit');

        try {
            $service->buildBranchMovers($start, $end, $limit);
            $this->info("Branch movers built successfully for {$start} → {$end} (limit={$limit}).");
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Branch movers build failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
