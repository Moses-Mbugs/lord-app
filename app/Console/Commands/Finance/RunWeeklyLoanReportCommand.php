<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Services\Reports\WeeklyLoanReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class RunWeeklyLoanReportCommand extends Command
{
    protected $signature = 'reports:run-weekly-loan
        {end? : End date YYYY-MM-DD (optional if --auto)}
        {--auto : Auto-resolve end date to today (Africa/Nairobi)}
        {--date= : Explicit end date for --auto mode (YYYY-MM-DD)}
        {--to= : Override TO recipients}
        {--cc= : Override CC recipients}
        {--limit=100 : Top movers per sub-segment in the Excel CIF drilldown}
    ';

    protected $description = 'Full weekly loan pipeline: build snapshot -> email report.';

    public function handle(WeeklyLoanReportService $service): int
    {
        try {
            $auto = (bool) $this->option('auto');

            if ($auto) {
                $dateOpt = trim((string) ($this->option('date') ?? ''));
                $weekEnd = $dateOpt !== ''
                    ? Carbon::parse($dateOpt)->toDateString()
                    : $service->findLatestBalanceDate();
            } else {
                $endArg = trim((string) ($this->argument('end') ?? ''));
                if ($endArg === '') {
                    $this->error('Missing end date. Provide {end} or use --auto.');
                    return self::FAILURE;
                }
                $weekEnd = Carbon::parse($endArg)->toDateString();
            }

            $this->info("Weekly loan pipeline for: {$weekEnd}");

            $this->info('1/2 Building weekly loan snapshot...');
            Artisan::call('reports:build-weekly-loan', ['end' => $weekEnd], $this->output);

            $emailArgs = ['end' => $weekEnd, '--limit' => (int) $this->option('limit')];

            $toOpt = trim((string) ($this->option('to') ?? ''));
            if ($toOpt !== '') $emailArgs['--to'] = $toOpt;

            $ccOpt = trim((string) ($this->option('cc') ?? ''));
            if ($ccOpt !== '') $emailArgs['--cc'] = $ccOpt;

            $this->info('2/2 Emailing report...');
            Artisan::call('reports:email-weekly-loan', $emailArgs, $this->output);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Weekly loan run failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
