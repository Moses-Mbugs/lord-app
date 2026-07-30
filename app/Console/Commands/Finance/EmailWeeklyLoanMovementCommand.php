<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Mail\WeeklyLoanMovementMail;
use App\Services\Reports\WeeklyLoanReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EmailWeeklyLoanMovementCommand extends Command
{
    protected $signature = 'reports:email-weekly-loan
        {end? : Report end date YYYY-MM-DD (defaults to today)}
        {--to= : Override TO recipients (comma/semicolon/space separated)}
        {--cc= : Override CC recipients (comma/semicolon/space separated)}
        {--limit=100 : Top movers per sub-segment in the Excel CIF drilldown}
        {--force-recompute : Ignore stored data and recompute from raw loan_listings}
    ';

    protected $description = 'Email weekly loan movement report. Reads from weekly_loan_snapshots (run reports:build-weekly-loan first).';

    public function handle(WeeklyLoanReportService $service): int
    {
        $endArg  = trim((string) ($this->argument('end') ?? ''));
        $weekEnd = $endArg !== ''
            ? Carbon::parse($endArg)->toDateString()
            : $service->findLatestBalanceDate();

        $limit = max(1, (int) $this->option('limit'));

        $toOpt = trim((string) ($this->option('to') ?? ''));
        $to = $toOpt !== ''
            ? $this->parseEmails($toOpt)
            : $this->parseEmails(config('reports.weekly_loan.to', []));

        if (empty($to)) {
            $this->error('No TO recipients configured. Set reports.weekly_loan.to or pass --to=');
            return self::FAILURE;
        }

        $ccOpt = trim((string) ($this->option('cc') ?? ''));
        $cc = $ccOpt !== ''
            ? $this->parseEmails($ccOpt)
            : $this->parseEmails(config('reports.weekly_loan.cc', []));

        $invalid = array_filter(array_merge($to, $cc), fn($e) => !filter_var($e, FILTER_VALIDATE_EMAIL));
        if (!empty($invalid)) {
            $this->error('Invalid email(s): ' . implode(', ', array_values($invalid)));
            return self::FAILURE;
        }

        $forceRecompute = (bool) $this->option('force-recompute');
        $data = null;

        if (!$forceRecompute) {
            $data = $service->loadFromTable($weekEnd);
        }

        if ($data === null) {
            if (!$forceRecompute) {
                $this->warn("No stored data found for {$weekEnd} — computing from raw data (run reports:build-weekly-loan to pre-build).");
            }
            $data = $service->build($weekEnd);
        } else {
            $this->info("Loaded stored snapshot for {$weekEnd}.");
        }

        $this->line("  Week  : {$data['periods']['week_start']} → {$data['periods']['week_end']}");
        $this->line("  MTD   : {$data['periods']['mtd_start']} → {$data['periods']['week_end']}");

        $drilldown = $service->drilldown(
            $data['periods']['week_start'],
            $data['periods']['week_end'],
            $limit
        );

        $weekTopMovers = $service->topMovers(
            $data['periods']['week_start'],
            $data['periods']['week_end'],
            10
        );

        $mtdTopMovers = $service->topMovers(
            $data['periods']['mtd_start'],
            $data['periods']['week_end'],
            10
        );

        Mail::to($to)
            ->cc($cc)
            ->send(new WeeklyLoanMovementMail(
                $weekEnd,
                $data,
                $drilldown,
                $to,
                $cc,
                $weekTopMovers,
                $mtdTopMovers
            ));

        $this->info('Weekly loan movement email sent.');
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
