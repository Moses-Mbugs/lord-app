<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Services\Reports\WeeklySegmentReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class RunWeeklySegmentReportCommand extends Command
{
    protected $signature = 'reports:run-weekly-segment
        {end? : End date YYYY-MM-DD (optional if --auto)}
        {--auto : Auto-resolve end date to today (Africa/Nairobi)}
        {--date= : Explicit end date for --auto mode (YYYY-MM-DD)}
        {--to= : Override TO recipients}
        {--cc= : Override CC recipients}
        {--limit=100 : Top overall gainers/losers (flat, not split by sub-segment) in the Excel CIF drilldown}
        {--start= : Override week_start YYYY-MM-DD (defaults to latest available balance date on/before end-7 days)}
    ';

    protected $description = 'Full weekly segment pipeline: build snapshot -> email report.';

    public function handle(WeeklySegmentReportService $service): int
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

            $this->info("Weekly segment pipeline for: {$weekEnd}");

            $startOpt = trim((string) ($this->option('start') ?? ''));

            // Step 1: Build and store snapshot
            $buildArgs = ['end' => $weekEnd];
            if ($startOpt !== '') $buildArgs['--start'] = $startOpt;

            $this->info('1/2 Building weekly segment snapshot...');
            Artisan::call('reports:build-weekly-segment', $buildArgs, $this->output);

            // Step 2: Email (reads from stored snapshot)
            $emailArgs = ['end' => $weekEnd, '--limit' => (int) $this->option('limit')];
            if ($startOpt !== '') $emailArgs['--start'] = $startOpt;

            $toOpt = trim((string) ($this->option('to') ?? ''));
            if ($toOpt !== '') $emailArgs['--to'] = $toOpt;

            $ccOpt = trim((string) ($this->option('cc') ?? ''));
            if ($ccOpt !== '') $emailArgs['--cc'] = $ccOpt;

            $this->info('2/2 Emailing report...');
            Artisan::call('reports:email-weekly-segment', $emailArgs, $this->output);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Weekly segment run failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
