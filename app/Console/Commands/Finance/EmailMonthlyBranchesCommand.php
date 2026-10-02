<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Mail\MonthlyBranchReportMail;
use App\Services\Reports\MonthlyPerformanceReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailMonthlyBranchesCommand extends Command
{
    protected $signature = 'reports:email-monthly-branches
        {month? : Month to report on, YYYY-MM (defaults to the previous calendar month)}
        {--to= : Override TO recipients (comma/semicolon/space separated)}
        {--cc= : Override CC recipients (comma/semicolon/space separated)}
    ';

    protected $description = 'Email the monthly branch performance report: Deposits (MoM + YTD), Loans (MoM) and NTB per branch.';

    public function handle(MonthlyPerformanceReportService $service): int
    {
        $month = trim((string) ($this->argument('month') ?? ''));
        if ($month === '') {
            $month = now()->timezone('Africa/Nairobi')->subMonthNoOverflow()->format('Y-m');
        }

        $toOpt = trim((string) ($this->option('to') ?? ''));
        $to = $this->parseEmails($toOpt !== '' ? $toOpt : config('reports.monthly_branches.to', []));

        if (empty($to)) {
            $this->error('No TO recipients configured. Set reports.monthly_branches.to or pass --to=');
            return self::FAILURE;
        }

        $ccOpt = trim((string) ($this->option('cc') ?? ''));
        $cc = $this->parseEmails($ccOpt !== '' ? $ccOpt : config('reports.monthly_branches.cc', []));

        $invalid = array_filter(array_merge($to, $cc), fn($e) => !filter_var($e, FILTER_VALIDATE_EMAIL));
        if (!empty($invalid)) {
            $this->error('Invalid email(s): ' . implode(', ', array_values($invalid)));
            return self::FAILURE;
        }

        $this->info("Building monthly branch report for {$month}...");

        try {
            $report = $service->buildBranches($month);
        } catch (Throwable $e) {
            $this->error('Monthly branch build failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $dep  = $report['deposit_periods'];
        $loan = $report['loan_periods'];

        $this->line("  Deposits : {$dep['month_start']} → {$dep['month_end']} (YTD from {$dep['ytd_start']})");
        $this->line($loan
            ? "  Loans    : {$loan['month_start']} → {$loan['month_end']}"
            : '  Loans    : no loan snapshot for this month or the one before — loan columns will be blank');

        Mail::to($to)->cc($cc)->send(new MonthlyBranchReportMail($report));

        $this->info('Monthly branch email sent.');
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
