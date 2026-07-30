<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Services\Reports\WeeklySegmentReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BuildWeeklySegmentCommand extends Command
{
    protected $signature = 'reports:build-weekly-segment
        {end? : Report end date YYYY-MM-DD (defaults to latest available balance date)}
        {--start= : Override week_start YYYY-MM-DD (defaults to latest available balance date on/before end-7 days)}
    ';

    protected $description = 'Build and store weekly segment movement snapshot (LCY + FCY) into weekly_segment_snapshots.';

    public function handle(WeeklySegmentReportService $service): int
    {
        $endArg  = trim((string) ($this->argument('end') ?? ''));
        $weekEnd = $endArg !== ''
            ? Carbon::parse($endArg)->toDateString()
            : $service->findLatestBalanceDate();

        $startOpt  = trim((string) ($this->option('start') ?? ''));
        $weekStart = $startOpt !== '' ? Carbon::parse($startOpt)->toDateString() : null;

        if ($weekStart !== null && !$service->hasBalanceDataOn($weekStart)) {
            $this->warn("No customer_balances rows found for {$weekStart} — movement against this date will show as the full balance, not a real delta.");
        }

        $this->info("Building weekly segment snapshot for week ending: {$weekEnd}" . ($weekStart ? " (start override: {$weekStart})" : ''));

        $data = $service->build($weekEnd, $weekStart);

        $this->line("  Week  : {$data['periods']['week_start']} → {$data['periods']['week_end']}");
        $this->line("  MTD   : {$data['periods']['mtd_start']} → {$data['periods']['week_end']}");
        $this->line("  YTD   : {$data['periods']['ytd_start']} → {$data['periods']['week_end']}");

        $count = $service->persist($data);

        $this->info("Done. {$count} rows stored for {$weekEnd}.");

        return self::SUCCESS;
    }
}
