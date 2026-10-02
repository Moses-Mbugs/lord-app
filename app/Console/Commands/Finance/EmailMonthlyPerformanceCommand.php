<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Mail\MonthlyPerformanceReportMail;
use App\Models\Finance\MonthlyReportSnapshot;
use App\Services\Reports\MonthlyPerformanceReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailMonthlyPerformanceCommand extends Command
{
    protected $signature = 'reports:email-monthly-performance
        {month? : Month to report on, YYYY-MM (defaults to the previous calendar month)}
        {--to= : Override TO recipients (comma/semicolon/space separated)}
        {--cc= : Override CC recipients (comma/semicolon/space separated)}
        {--limit=100 : Top CIF gainers/losers per product in the Excel attachment (applies when building)}
        {--rebuild : Ignore the stored copy in monthly_report_snapshots and rebuild it}
    ';

    protected $description = 'Email the monthly Loans & Deposits performance report: Deposits (MoM + YTD) and Loans (MoM) by segment.';

    public function handle(MonthlyPerformanceReportService $service): int
    {
        $month = trim((string) ($this->argument('month') ?? ''));
        if ($month === '') {
            $month = now()->timezone('Africa/Nairobi')->subMonthNoOverflow()->format('Y-m');
        }

        $toOpt = trim((string) ($this->option('to') ?? ''));
        $to = $this->parseEmails($toOpt !== '' ? $toOpt : config('reports.monthly_performance.to', []));

        if (empty($to)) {
            $this->error('No TO recipients configured. Set reports.monthly_performance.to or pass --to=');
            return self::FAILURE;
        }

        $ccOpt = trim((string) ($this->option('cc') ?? ''));
        $cc = $this->parseEmails($ccOpt !== '' ? $ccOpt : config('reports.monthly_performance.cc', []));

        $invalid = array_filter(array_merge($to, $cc), fn($e) => !filter_var($e, FILTER_VALIDATE_EMAIL));
        if (!empty($invalid)) {
            $this->error('Invalid email(s): ' . implode(', ', array_values($invalid)));
            return self::FAILURE;
        }

        try {
            $report = $this->option('rebuild') ? null : $service->loadFresh(MonthlyReportSnapshot::TYPE_LOANS_DEPOSITS, $month);

            if ($report !== null) {
                $this->info("Loaded stored Loans & Deposits report for {$month} (built {$report['built_at']}).");
            } else {
                $this->info("Building Loans & Deposits report for {$month}...");
                $report = $service->build($month, max(1, (int) $this->option('limit')));
                $service->save(MonthlyReportSnapshot::TYPE_LOANS_DEPOSITS, $report);
            }
        } catch (Throwable $e) {
            $this->error('Monthly performance build failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $dep  = $report['deposits']['periods'];
        $loan = $report['loans']['periods'];

        $this->line("  Deposits : {$dep['month_start']} → {$dep['month_end']} (YTD from {$dep['ytd_start']})");
        $this->line($loan
            ? "  Loans    : {$loan['month_start']} → {$loan['month_end']}"
            : "  Loans    : no loan snapshot for {$report['loans']['missing']} — section will be empty");

        Mail::to($to)->cc($cc)->send(new MonthlyPerformanceReportMail($report));

        $this->info('Monthly performance email sent.');
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
