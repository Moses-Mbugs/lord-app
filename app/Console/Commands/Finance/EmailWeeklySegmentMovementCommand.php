<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Mail\WeeklySegmentMovementMail;
use App\Services\Reports\WeeklySegmentReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EmailWeeklySegmentMovementCommand extends Command
{
    protected $signature = 'reports:email-weekly-segment
        {end? : Report end date YYYY-MM-DD (defaults to today)}
        {--to= : Override TO recipients (comma/semicolon/space separated)}
        {--cc= : Override CC recipients (comma/semicolon/space separated)}
        {--limit=100 : Top overall gainers/losers, whole bank any currency (flat, not split by sub-segment) in the Excel CIF drilldown}
        {--force-recompute : Ignore stored data and recompute from raw customer_balances}
        {--start= : Override week_start YYYY-MM-DD. Forces a live recompute (ignores any stored snapshot).}
    ';

    protected $description = 'Email weekly segment movement report. Reads from weekly_segment_snapshots (run reports:build-weekly-segment first).';

    public function handle(WeeklySegmentReportService $service): int
    {
        $endArg  = trim((string) ($this->argument('end') ?? ''));
        $weekEnd = $endArg !== ''
            ? Carbon::parse($endArg)->toDateString()
            : $service->findLatestBalanceDate();

        $limit = max(1, (int) $this->option('limit'));

        $toOpt = trim((string) ($this->option('to') ?? ''));
        $to = $toOpt !== ''
            ? $this->parseEmails($toOpt)
            : $this->parseEmails(config('reports.weekly_segment.to', []));

        if (empty($to)) {
            $this->error('No TO recipients configured. Set reports.weekly_segment.to or pass --to=');
            return self::FAILURE;
        }

        $ccOpt = trim((string) ($this->option('cc') ?? ''));
        $cc = $ccOpt !== ''
            ? $this->parseEmails($ccOpt)
            : $this->parseEmails(config('reports.weekly_segment.cc', []));

        $invalid = array_filter(array_merge($to, $cc), fn($e) => !filter_var($e, FILTER_VALIDATE_EMAIL));
        if (!empty($invalid)) {
            $this->error('Invalid email(s): ' . implode(', ', array_values($invalid)));
            return self::FAILURE;
        }

        $startOpt  = trim((string) ($this->option('start') ?? ''));
        $weekStart = $startOpt !== '' ? Carbon::parse($startOpt)->toDateString() : null;

        if ($weekStart !== null && !$service->hasBalanceDataOn($weekStart)) {
            $this->warn("No customer_balances rows found for {$weekStart} — movement against this date will show as the full balance, not a real delta.");
        }

        // Load from table (fast) unless --force-recompute or --start is set
        $forceRecompute = (bool) $this->option('force-recompute') || $weekStart !== null;
        $data = null;

        if (!$forceRecompute) {
            $data = $service->loadFromTable($weekEnd);
        }

        if ($data === null) {
            if (!$forceRecompute) {
                $this->warn("No stored data found for {$weekEnd} — computing from raw data (run reports:build-weekly-segment to pre-build).");
            }
            $data = $service->build($weekEnd, $weekStart);
        } else {
            $this->info("Loaded stored snapshot for {$weekEnd}.");
        }

        $this->line("  Week  : {$data['periods']['week_start']} → {$data['periods']['week_end']}");
        $this->line("  MTD   : {$data['periods']['mtd_start']} → {$data['periods']['week_end']}");
        $this->line("  YTD   : {$data['periods']['ytd_start']} → {$data['periods']['week_end']}");

        $drilldown         = $service->topMovers(
            $data['periods']['week_start'],
            $data['periods']['week_end'],
            $limit
        );

        $historicalSection = $service->buildHistoricalSection($weekEnd);

        Mail::to($to)
            ->cc($cc)
            ->send(new WeeklySegmentMovementMail(
                $weekEnd,
                $data,
                $drilldown,
                $historicalSection,
                $to,
                $cc
            ));

        $this->info('Weekly segment movement email sent.');
        $this->line('TO: ' . implode(', ', $to));
        $this->line('CC: ' . (empty($cc) ? '(none)' : implode(', ', $cc)));

        return self::SUCCESS;
    }

    private function parseEmails(array|string|null $input): array
    {
        if (is_array($input)) {
            $emails = $input;
        } else {
            $raw = trim((string) ($input ?? ''));
            if ($raw === '') return [];
            $emails = preg_split('/[,\s;]+/', $raw) ?: [];
        }

        $emails = array_map(fn($e) => strtolower(trim((string) $e)), $emails);
        $emails = array_values(array_filter($emails, fn($e) => $e !== ''));
        return array_values(array_unique($emails));
    }
}
