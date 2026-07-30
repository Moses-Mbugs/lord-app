<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Services\Reports\WeeklyLoanReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BuildWeeklyLoanCommand extends Command
{
    protected $signature = 'reports:build-weekly-loan
        {end? : Report end date YYYY-MM-DD (defaults to latest available as_at_date)}
    ';

    protected $description = 'Build and store weekly loan movement snapshot (LCY + FCY) into weekly_loan_snapshots.';

    public function handle(WeeklyLoanReportService $service): int
    {
        $endArg  = trim((string) ($this->argument('end') ?? ''));
        $weekEnd = $endArg !== ''
            ? Carbon::parse($endArg)->toDateString()
            : $service->findLatestBalanceDate();

        $this->info("Building weekly loan snapshot for week ending: {$weekEnd}");

        $data = $service->build($weekEnd);

        $this->line("  Week  : {$data['periods']['week_start']} → {$data['periods']['week_end']}");
        $this->line("  MTD   : {$data['periods']['mtd_start']} → {$data['periods']['week_end']}");

        $count = $service->persist($data);

        $this->info("Done. {$count} rows stored for {$weekEnd}.");

        return self::SUCCESS;
    }
}
