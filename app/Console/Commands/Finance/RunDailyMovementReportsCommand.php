<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class RunDailyMovementReportsCommand extends Command
{
    protected $signature = 'reports:run-daily
        {date? : Optional end date YYYY-MM-DD (defaults to yesterday)}
        {--import-deposits= : Path to deposit listing folder (optional)}
        {--import-balances= : Path to balances folder (optional)}
        {--limit=20 : Top movers limit per bucket (LCY gain/loss, FCY gain/loss)}
        {--to= : Top movers email TO override}
        {--cc= : Top movers email CC override (comma-separated)}
    ';

    protected $description = 'Run daily imports + build snapshots + build & email top movers + send deposit movement report.';

    public function handle(): int
    {
        try {
            // Use yesterday as default "end" date (common in EOD reporting)
            $endDate = $this->argument('date')
                ? Carbon::parse((string) $this->argument('date'))->toDateString()
                : now()->subDay()->toDateString();

            // Start date = day before end date
            $startDate = Carbon::parse($endDate)->subDay()->toDateString();

            $limit = (int) $this->option('limit');

            $depositsPath = (string) ($this->option('import-deposits') ?: config('reports.paths.deposits'));
            $balancesPath = (string) ($this->option('import-balances') ?: config('reports.paths.balances'));

            $to = (string) ($this->option('to') ?: config('reports.top_movers.to', env('TOP_MOVERS_EMAIL_TO')));
            $cc = (string) ($this->option('cc') ?: config('reports.top_movers.cc', env('TOP_MOVERS_EMAIL_CC', '')));

            $this->info("Running daily reports for {$startDate} → {$endDate}");
            $this->line("Limit: {$limit}");

            // 1) Import deposits (optional if path configured)
            if ($depositsPath && is_dir($depositsPath)) {
                $this->info("1/6 Importing deposits from: {$depositsPath}");
                Artisan::call('deposits:import', ['path' => $depositsPath], $this->output);
            } else {
                $this->warn("1/6 Skipped deposits import (path missing or not configured).");
            }

            // 2) Build movement snapshots (end date)
            $this->info("2/6 Building movement snapshots for: {$endDate}");
            Artisan::call('reports:build-snapshots', ['date' => $endDate], $this->output);

            // 3) Import balances (optional if path configured)
            if ($balancesPath && is_dir($balancesPath)) {
                $this->info("3/6 Importing balances from: {$balancesPath}");
                Artisan::call('import:balances', ['path' => $balancesPath], $this->output);
            } else {
                $this->warn("3/6 Skipped balances import (path missing or not configured).");
            }

            // 4) Build top movers LCY + FCY
            $this->info("4/6 Building top movers LCY");
            Artisan::call('reports:build-top-movers', [
                'start' => $startDate,
                'end' => $endDate,
                'currencyType' => 'LCY',
                '--limit' => $limit,
            ], $this->output);

            $this->info("4/6 Building top movers FCY");
            Artisan::call('reports:build-top-movers', [
                'start' => $startDate,
                'end' => $endDate,
                'currencyType' => 'FCY',
                '--limit' => $limit,
            ], $this->output);

            // 5) Email top movers (reads from top_movers)
            if ($to) {
                $this->info("5/6 Emailing top movers to: {$to}");
                Artisan::call('reports:email-top-movers', [
                    'start' => $startDate,
                    'end' => $endDate,
                    '--to' => $to,
                    '--cc' => $cc,
                    '--limit' => $limit,
                ], $this->output);
            } else {
                $this->warn("5/6 Skipped top movers email (no recipient configured).");
            }

            // 6) Send deposit movement report (your existing command)
            $this->info("6/6 Sending deposit movement report");
            Artisan::call('report:send-deposit-movement', [
                'start' => $startDate,
                'end' => $endDate,
            ], $this->output);

            $this->info("Daily reports completed successfully.");
            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->error("Daily reports failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
