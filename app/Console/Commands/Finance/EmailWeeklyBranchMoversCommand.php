<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Exports\Finance\WeeklyBranchMoversWorkbookExport;
use App\Mail\WeeklyBranchMoversReportMail;
use App\Services\Reports\BranchPeriodMetricsService;
use App\Services\Reports\GroupMoversService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class EmailWeeklyBranchMoversCommand extends Command
{
    protected $signature = 'reports:email-weekly-branch-movers
        {end? : Report end date YYYY-MM-DD (defaults to latest available balance date)}
        {--to= : Override TO recipients (comma/semicolon/space separated)}
        {--cc= : Override CC recipients (comma/semicolon/space separated)}
        {--limit=10 : Top N gainers/losers}
        {--auto-build : Build branch movers for each period if data is not already stored}
    ';

    protected $description = 'Email Weekly Branch Movers report: Deposits (WTD/MTD/YTD), Loans (WTD/MTD), and NTB (WTD/MTD/YTD) movement per branch.';

    public function handle(GroupMoversService $service, BranchPeriodMetricsService $metrics): int
    {
        $endArg  = trim((string) ($this->argument('end') ?? ''));
        $weekEnd = $endArg !== '' ? Carbon::parse($endArg)->toDateString() : $this->findLatestBalanceDate();

        if (!$weekEnd) {
            $this->error('Cannot determine end date — no balance data found and no end argument provided.');
            return self::FAILURE;
        }

        $limit     = max(1, (int) $this->option('limit'));
        $autoBuild = (bool) $this->option('auto-build');

        // Resolve TO recipients
        $toOpt = (string) ($this->option('to') ?? '');
        $to = $toOpt !== ''
            ? $this->parseEmails($toOpt)
            : $this->parseEmails(config(
                'reports.balances.weekly_branch_movers_to',
                config('reports.balances.branch_movers_to', [])
            ));

        if (empty($to)) {
            $this->error('No TO recipients configured. Set reports.balances.weekly_branch_movers_to or pass --to=');
            return self::FAILURE;
        }

        // Resolve CC recipients
        $ccOpt = (string) ($this->option('cc') ?? '');
        $cc = $ccOpt !== ''
            ? $this->parseEmails($ccOpt)
            : $this->parseEmails(config(
                'reports.balances.weekly_branch_movers_cc',
                config('reports.balances.branch_movers_cc', [])
            ));

        // Validate emails
        $invalid = array_values(array_filter(
            array_merge($to, $cc),
            fn ($e) => !filter_var($e, FILTER_VALIDATE_EMAIL)
        ));
        if (!empty($invalid)) {
            $this->error('Invalid email(s): ' . implode(', ', $invalid));
            return self::FAILURE;
        }

        // Deposits: WTD/MTD/YTD. Loans: WTD/MTD only. NTB: WTD/MTD/YTD.
        $weekEndDate = Carbon::parse($weekEnd);
        $periods = [
            'week' => [
                'start' => $this->resolveWeekStart($weekEnd),
                'end'   => $weekEnd,
                'label' => 'Weekly',
            ],
            'mtd' => [
                'start' => $weekEndDate->copy()->startOfMonth()->subDay()->toDateString(),
                'end'   => $weekEnd,
                'label' => 'MTD',
            ],
            'ytd' => [
                'start' => $weekEndDate->copy()->startOfYear()->subDay()->toDateString(),
                'end'   => $weekEnd,
                'label' => 'YTD',
            ],
        ];

        $this->line("Week ending : {$weekEnd}");
        $this->line("  Weekly    : {$periods['week']['start']} → {$weekEnd}");
        $this->line("  MTD       : {$periods['mtd']['start']} → {$weekEnd}");
        $this->line("  YTD       : {$periods['ytd']['start']} → {$weekEnd}");

        // Fetch / build data for each period
        $data = [];
        foreach ($periods as $key => $period) {
            [$summary, $top] = $metrics->groupMovers($period['start'], $period['end']);

            if ($summary->isEmpty() && $top->isEmpty()) {
                if ($autoBuild) {
                    $this->line("  Building {$period['label']} ({$period['start']} → {$period['end']})…");
                    $service->buildBranchMovers($period['start'], $period['end'], $limit);
                    [$summary, $top] = $metrics->groupMovers($period['start'], $period['end']);
                } else {
                    $this->warn("  No data for {$period['label']} ({$period['start']} → {$period['end']}).");
                    $this->warn("  Run: php artisan reports:build-branch-movers {$period['start']} {$period['end']} --limit={$limit}");
                    $this->warn("  Or re-run this command with --auto-build to build on-the-fly.");
                }
            }

            // Enrich summary rows with loan data + NTB (new accounts opened in this period)
            $loanByBranch = $metrics->loanTotals($period['start'], $period['end']);
            $ntbByBranch  = $metrics->ntbCounts($period['start'], $period['end']);
            $summary = $summary->map(function ($row) use ($loanByBranch, $ntbByBranch) {
                $code = strtoupper(trim((string) ($row->group_key ?? '')));
                $loan = $loanByBranch[$code] ?? ['open' => 0.0, 'close' => 0.0];
                $row->loan_open     = $loan['open'];
                $row->loan_close    = $loan['close'];
                $row->loan_movement = round($loan['close'] - $loan['open'], 2);
                $row->ntb_count     = $ntbByBranch[$code] ?? 0;
                return $row;
            });

            $data[$key] = [
                'period'     => $period,
                'summary'    => $summary,
                'topGainers' => $top->where('direction', 'GAIN')->sortBy('rank')->values(),
                'topLosers'  => $top->where('direction', 'LOSS')->sortBy('rank')->values(),
            ];
        }

        // Build Excel attachment
        $excelName   = "Weekly_Branch_Movers_{$weekEnd}.xlsx";
        $excelBinary = Excel::raw(
            new WeeklyBranchMoversWorkbookExport($weekEnd, $periods, $data, $limit),
            ExcelWriter::XLSX
        );

        $mailable = new WeeklyBranchMoversReportMail($weekEnd, $periods, $data, $limit);
        $mailable->attachData(
            $excelBinary,
            $excelName,
            ['mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );

        Mail::to($to)->cc($cc)->send($mailable);

        $this->info('Weekly branch movers email sent.');
        $this->line('TO: ' . implode(', ', $to));
        $this->line('CC: ' . (empty($cc) ? '(none)' : implode(', ', $cc)));

        return self::SUCCESS;
    }

    /**
     * Latest balance_date on or before (weekEnd − 7 days) — matches
     * WeeklySegmentReportService::findWeekStart() / WeeklyLoanReportService::findWeekStart()
     * so the Weekly column lines up with the Deposits and Loans weekly reports instead of
     * drifting to the calendar Monday of that week.
     */
    private function resolveWeekStart(string $weekEnd): string
    {
        $target = Carbon::parse($weekEnd)->subDays(7)->toDateString();

        $d = DB::table('customer_balances')
            ->where('balance_date', '<=', $target)
            ->max('balance_date');

        return $d ? Carbon::parse((string) $d)->toDateString() : $target;
    }

    private function findLatestBalanceDate(): ?string
    {
        $date = DB::table('customer_balances')
            ->whereNotNull('balance_date')
            ->max('balance_date');

        return $date ? Carbon::parse((string) $date)->toDateString() : null;
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

        $emails = array_map(fn ($e) => strtolower(trim((string) $e)), $emails);
        $emails = array_values(array_filter($emails, fn ($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)));

        return array_values(array_unique($emails));
    }
}
