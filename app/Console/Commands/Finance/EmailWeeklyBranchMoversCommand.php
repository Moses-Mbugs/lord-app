<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Exports\Finance\WeeklyBranchMoversWorkbookExport;
use App\Mail\WeeklyBranchMoversReportMail;
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
        {--auto-build : Build branch movers for the week if data is not already stored}
    ';

    protected $description = 'Email Weekly Branch Movers report: weekly deposits, loans, and NTB (new accounts) movement per branch.';

    public function handle(GroupMoversService $service): int
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

        // Weekly Branch Movers is week-only: Deposits, Loans, and NTB movement for the week.
        $period = [
            'start' => $this->resolveWeekStart($weekEnd),
            'end'   => $weekEnd,
            'label' => 'Weekly',
        ];

        $this->line("Week ending : {$weekEnd}");
        $this->line("  Weekly    : {$period['start']} → {$weekEnd}");

        [$summary, $top] = $this->fetchGroupMovers($period['start'], $period['end']);

        if ($summary->isEmpty() && $top->isEmpty()) {
            if ($autoBuild) {
                $this->line("  Building Weekly ({$period['start']} → {$period['end']})…");
                $service->buildBranchMovers($period['start'], $period['end'], $limit);
                [$summary, $top] = $this->fetchGroupMovers($period['start'], $period['end']);
            } else {
                $this->warn("  No data for Weekly ({$period['start']} → {$period['end']}).");
                $this->warn("  Run: php artisan reports:build-branch-movers {$period['start']} {$period['end']} --limit={$limit}");
                $this->warn("  Or re-run this command with --auto-build to build on-the-fly.");
            }
        }

        // Enrich summary rows with loan data + NTB (new accounts opened this week)
        $loanByBranch = $this->fetchBranchLoanData($period['start'], $period['end']);
        $ntbByBranch  = $this->fetchBranchNtbCounts($period['start'], $period['end']);
        $summary = $summary->map(function ($row) use ($loanByBranch, $ntbByBranch) {
            $code = strtoupper(trim((string) ($row->group_key ?? '')));
            $loan = $loanByBranch[$code] ?? ['open' => 0.0, 'close' => 0.0];
            $row->loan_open     = $loan['open'];
            $row->loan_close    = $loan['close'];
            $row->loan_movement = round($loan['close'] - $loan['open'], 2);
            $row->ntb_count     = $ntbByBranch[$code] ?? 0;
            return $row;
        });

        $data = [
            'period'     => $period,
            'summary'    => $summary,
            'topGainers' => $top->where('direction', 'GAIN')->sortBy('rank')->values(),
            'topLosers'  => $top->where('direction', 'LOSS')->sortBy('rank')->values(),
        ];

        // Build Excel attachment
        $excelName   = "Weekly_Branch_Movers_{$weekEnd}.xlsx";
        $excelBinary = Excel::raw(
            new WeeklyBranchMoversWorkbookExport($weekEnd, $period, $data, $limit),
            ExcelWriter::XLSX
        );

        $mailable = new WeeklyBranchMoversReportMail($weekEnd, $period, $data, $limit);
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

    private function fetchGroupMovers(string $start, string $end): array
    {
        $summary = DB::table('group_movers')
            ->where('group_type', 'BRANCH')
            ->where('scope', 'SUMMARY')
            ->whereDate('start_date', $start)
            ->whereDate('end_date', $end)
            ->orderByRaw("
                CASE
                    WHEN group_key = '834' THEN 1
                    WHEN group_key = '950' THEN 2
                    WHEN group_key = 'ALL' THEN 3
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

    private function fetchBranchLoanData(string $start, string $end): array
    {
        $loanStartDate = DB::table('loan_listings')
            ->whereNotNull('as_at_date')
            ->whereDate('as_at_date', '<=', $start)
            ->max('as_at_date');

        $loanEndDate = DB::table('loan_listings')
            ->whereNotNull('as_at_date')
            ->whereDate('as_at_date', '<=', $end)
            ->max('as_at_date');

        if (!$loanStartDate || !$loanEndDate || $loanStartDate === $loanEndDate) {
            $latest = DB::table('loan_listings')
                ->whereNotNull('as_at_date')
                ->select(DB::raw('DATE(as_at_date) AS snap_date'))
                ->distinct()
                ->orderByDesc('snap_date')
                ->limit(2)
                ->pluck('snap_date');

            if ($latest->count() < 2) return [];

            $loanEndDate   = (string) $latest->first();
            $loanStartDate = (string) $latest->last();
        }

        $dates = array_values(array_unique([$loanStartDate, $loanEndDate]));

        $rows = DB::table('loan_listings as ll')
            ->joinSub(
                DB::table('loan_listings')
                    ->whereIn(DB::raw('DATE(as_at_date)'), $dates)
                    ->whereRaw("UPPER(TRIM(COALESCE(business_segment,''))) != 'CORPORATE'")
                    ->whereRaw("(TRIM(COALESCE(loan_status, '')) = '' OR loan_status IN ('NORM', 'Normal', 'OAEM', 'SUBS', 'Watch'))")
                    ->select(DB::raw('DATE(as_at_date) AS snap_date'), 'related_account', DB::raw('MAX(id) AS max_id'))
                    ->groupBy(DB::raw('DATE(as_at_date)'), 'related_account'),
                'dedup', 'll.id', '=', 'dedup.max_id'
            )
            ->selectRaw(
                "UPPER(TRIM(COALESCE(NULLIF(TRIM(ll.branch),''), LEFT(ll.related_account, 3)))) AS branch_code,
                 SUM(CASE WHEN DATE(ll.as_at_date) = ? THEN ll.loan_book_outstanding ELSE 0 END) AS loan_open,
                 SUM(CASE WHEN DATE(ll.as_at_date) = ? THEN ll.loan_book_outstanding ELSE 0 END) AS loan_close",
                [$loanStartDate, $loanEndDate]
            )
            ->groupByRaw("UPPER(TRIM(COALESCE(NULLIF(TRIM(ll.branch),''), LEFT(ll.related_account, 3))))")
            ->get();

        $result   = [];
        $allOpen  = 0.0;
        $allClose = 0.0;

        foreach ($rows as $r) {
            $code = strtoupper(trim((string) $r->branch_code));
            if ($code === '') continue;
            $result[$code] = ['open' => (float) $r->loan_open, 'close' => (float) $r->loan_close];
            $allOpen  += (float) $r->loan_open;
            $allClose += (float) $r->loan_close;
        }

        $result['ALL'] = ['open' => $allOpen, 'close' => $allClose];

        return $result;
    }

    /**
     * NTB — distinct CIFs with a new account opened in (start, end] — per branch, plus 'ALL'.
     * Exclusive of $start / inclusive of $end so back-to-back periods (e.g. this week's end
     * being next week's start) never double-count an account opened on the boundary date.
     *
     * ac_open_date is stored as free text in D-Mon-YY form (e.g. "22-Oct-24") despite the
     * migration declaring a DATE column — STR_TO_DATE is required to parse it, mirroring
     * BranchDailyPerformanceSummaryService's NTB calculation. P50 (Head Office) is excluded,
     * matching every other figure in this report.
     */
    private function fetchBranchNtbCounts(string $start, string $end): array
    {
        $base = DB::table('customer_accounts_imports')
            ->whereNotNull('branch_code')
            ->whereNotNull('f12_cif')
            ->whereNotNull('ac_open_date')
            ->whereRaw("TRIM(ac_open_date) <> ''")
            ->whereRaw("UPPER(TRIM(branch_code)) <> 'P50'")
            ->whereRaw("STR_TO_DATE(ac_open_date, '%d-%b-%y') > ?", [$start])
            ->whereRaw("STR_TO_DATE(ac_open_date, '%d-%b-%y') <= ?", [$end]);

        $rows = (clone $base)
            ->selectRaw("UPPER(TRIM(branch_code)) as branch_code, COUNT(DISTINCT f12_cif) as ntb_count")
            ->groupByRaw("UPPER(TRIM(branch_code))")
            ->get();

        $result = [];
        foreach ($rows as $r) {
            $code = strtoupper(trim((string) $r->branch_code));
            if ($code === '') continue;
            $result[$code] = (int) $r->ntb_count;
        }

        // Distinct across all branches (not a sum of the per-branch counts above), in case the
        // same CIF opened accounts at more than one branch within the period.
        $result['ALL'] = (int) ((clone $base)->selectRaw('COUNT(DISTINCT f12_cif) as agg')->value('agg') ?? 0);

        return $result;
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
