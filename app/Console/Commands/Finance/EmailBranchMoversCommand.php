<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Exports\Finance\BranchMoversWorkbookExport;
use App\Mail\BranchMoversReportMail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class EmailBranchMoversCommand extends Command
{
    protected $signature = 'reports:email-branch-movers
        {start : Start date YYYY-MM-DD (requested start)}
        {end : End date YYYY-MM-DD}
        {--to= : Override TO recipients (comma/semicolon/space separated)}
        {--cc= : Override CC recipients (comma/semicolon/space separated)}
        {--limit=10 : Limit for top gainers/losers}
    ';

    protected $description = 'Email Branch Movers report (reads from group_movers table). Supports fallback start_date for a given end date. Attaches Excel workbook.';

    public function handle(): int
    {
        $requestedStart = Carbon::parse((string) $this->argument('start'))->toDateString();
        $end            = Carbon::parse((string) $this->argument('end'))->toDateString();
        $limit          = max(1, (int) $this->option('limit'));

        // TO
        $toOpt = (string) ($this->option('to') ?? '');
        $to = $toOpt !== ''
            ? $this->parseEmails($toOpt)
            : $this->parseEmails(config('reports.balances.branch_movers_to', []));

        if (empty($to)) {
            $this->error('No TO recipients configured. Set reports.balances.branch_movers_to or pass --to=');
            return self::FAILURE;
        }

        // CC
        $ccOpt = (string) ($this->option('cc') ?? '');
        $cc = $ccOpt !== ''
            ? $this->parseEmails($ccOpt)
            : $this->parseEmails(config('reports.balances.branch_movers_cc', []));

        // 1) Try requested start
        $effectiveStart = $requestedStart;
        [$summary, $top] = $this->fetch($effectiveStart, $end);

        // 2) Fallback: if empty, use latest start_date available for this end_date
        if ($summary->isEmpty() && $top->isEmpty()) {
            $fallbackStart = DB::table('group_movers')
                ->where('group_type', 'BRANCH')
                ->whereDate('end_date', $end)
                ->max('start_date');

            if ($fallbackStart) {
                $effectiveStart = Carbon::parse((string) $fallbackStart)->toDateString();
                [$summary, $top] = $this->fetch($effectiveStart, $end);
            }
        }

        if ($summary->isEmpty() && $top->isEmpty()) {
            $this->warn("No rows found in group_movers for requested {$requestedStart} â†’ {$end} (and no fallback found).");
            $this->warn("Run: php artisan reports:build-branch-movers <start> <end> --limit={$limit}");
            return self::FAILURE;
        }

        if ($effectiveStart !== $requestedStart) {
            $this->line("âš  Fallback applied: using {$effectiveStart} â†’ {$end} (requested was {$requestedStart} â†’ {$end})");
        }

        $topGainers = $top->where('direction', 'GAIN')->sortBy('rank')->take($limit)->values();
        $topLosers  = $top->where('direction', 'LOSS')->sortBy('rank')->take($limit)->values();

        // Build Excel attachment (deposits only: summary, movement, Deposit CIF)
        $excelName = "Branch_Movers_{$effectiveStart}_{$end}.xlsx";
        $excelBinary = Excel::raw(
            new BranchMoversWorkbookExport($effectiveStart, $end, $limit),
            ExcelWriter::XLSX
        );

        // Build email + attach
        $mailable = new BranchMoversReportMail(
            $effectiveStart, $end, $summary, $topGainers, $topLosers, $limit
        );
        $mailable->attachData(
            $excelBinary,
            $excelName,
            ['mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );

        Mail::to($to)->cc($cc)->send($mailable);

        $this->info('Branch movers email sent (with Excel attachment).');
        $this->line('TO: ' . implode(', ', $to));
        $this->line('CC: ' . (empty($cc) ? '(none)' : implode(', ', $cc)));
        $this->line("Period: {$effectiveStart} â†’ {$end} | Top Limit: {$limit}");

        return self::SUCCESS;
    }

    private function fetch(string $start, string $end): array
    {
        $summary = DB::table('group_movers')
            ->where('group_type', 'BRANCH')
            ->where('scope', 'SUMMARY')
            ->whereDate('start_date', $start)
            ->whereDate('end_date', $end)
            ->orderByRaw("
                CASE
                    WHEN group_key = '834' THEN 1  -- Express Accounts
                    WHEN group_key = '950' THEN 2  -- Fingo Accounts
                    WHEN group_key = 'ALL' THEN 3  -- TOTAL last
                    ELSE 0
                END
            ")
            ->orderBy('group_key')
            ->get();

        $top = DB::table('group_movers')
            ->where('group_type', 'BRANCH')
            ->where('scope', 'TOP')
            ->whereDate('start_date', $start)
            ->whereDate('end_date', $end)
            ->orderBy('direction')
            ->orderBy('rank')
            ->get();

        return [$summary, $top];
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
        $emails = array_values(array_filter($emails, fn($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)));
        return array_values(array_unique($emails));
    }
}
