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
            $this->warn("No rows found in group_movers for requested {$requestedStart} → {$end} (and no fallback found).");
            $this->warn("Run: php artisan reports:build-branch-movers <start> <end> --limit={$limit}");
            return self::FAILURE;
        }

        if ($effectiveStart !== $requestedStart) {
            $this->line("⚠ Fallback applied: using {$effectiveStart} → {$end} (requested was {$requestedStart} → {$end})");
        }

        $topGainers = $top->where('direction', 'GAIN')->sortBy('rank')->take($limit)->values();
        $topLosers  = $top->where('direction', 'LOSS')->sortBy('rank')->take($limit)->values();

        $loanByBranch = $this->fetchBranchLoanData($effectiveStart, $end);
        $summary = $summary->map(function ($row) use ($loanByBranch) {
            $key  = strtoupper(trim((string) ($row->group_key ?? '')));
            $loan = $loanByBranch[$key] ?? ['open' => 0.0, 'close' => 0.0];
            $row->loan_open     = $loan['open'];
            $row->loan_close    = $loan['close'];
            $row->loan_movement = round($loan['close'] - $loan['open'], 2);
            return $row;
        });

        $branchNameMap = $summary->reduce(function ($carry, $row) {
            $key = strtoupper(trim((string) ($row->group_key ?? '')));
            if ($key !== '' && $key !== 'ALL') {
                $carry[$key] = (string) ($row->group_name ?? $key);
            }
            return $carry;
        }, []);

        $loanMvRows = collect();
        foreach ($loanByBranch as $code => $v) {
            if ($code === 'ALL' || $code === '') continue;
            $mv = round($v['close'] - $v['open'], 2);
            if ($mv == 0) continue;
            $loanMvRows->push((object) [
                'branch_code'   => $code,
                'branch_name'   => $branchNameMap[$code] ?? $code,
                'loan_open'     => $v['open'],
                'loan_close'    => $v['close'],
                'loan_movement' => $mv,
            ]);
        }

        $topLoanGainers = $loanMvRows
            ->filter(fn ($r) => $r->loan_movement > 0)
            ->sortByDesc(fn ($r) => $r->loan_movement)
            ->take($limit)->values()
            ->map(function ($r, $i) { $r->rank = $i + 1; return $r; });

        $topLoanLosers = $loanMvRows
            ->filter(fn ($r) => $r->loan_movement < 0)
            ->sortBy(fn ($r) => $r->loan_movement)
            ->take($limit)->values()
            ->map(function ($r, $i) { $r->rank = $i + 1; return $r; });

        $loanAccountMovers = $this->fetchLoanAccountMovers($effectiveStart, $end, $limit);

        // Build Excel attachment (BRANCH + BRANCH_CIF sheets)
        $excelName = "Branch_Movers_{$effectiveStart}_{$end}.xlsx";
        $excelBinary = Excel::raw(
            new BranchMoversWorkbookExport($effectiveStart, $end, $limit),
            ExcelWriter::XLSX
        );

        // Build email + attach
        $mailable = new BranchMoversReportMail(
            $effectiveStart, $end, $summary, $topGainers, $topLosers, $limit,
            $topLoanGainers, $topLoanLosers, $loanAccountMovers
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
        $this->line("Period: {$effectiveStart} → {$end} | Top Limit: {$limit}");

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

    /**
     * Per-account loan movements grouped by branch code.
     * Returns Collection keyed by branch_code → ['branch_name', 'gainers', 'losers'].
     */
    private function fetchLoanAccountMovers(string $start, string $end, int $limit): \Illuminate\Support\Collection
    {
        $loanStartDate = DB::table('loan_listings')
            ->whereNotNull('as_at_date')->whereDate('as_at_date', '<=', $start)->max('as_at_date');
        $loanEndDate = DB::table('loan_listings')
            ->whereNotNull('as_at_date')->whereDate('as_at_date', '<=', $end)->max('as_at_date');

        // If no distinct pair found, fall back to the two most recent snapshots in the system
        if (!$loanStartDate || !$loanEndDate || $loanStartDate === $loanEndDate) {
            $latest = DB::table('loan_listings')
                ->whereNotNull('as_at_date')
                ->select(DB::raw('DATE(as_at_date) AS snap_date'))
                ->distinct()
                ->orderByDesc('snap_date')
                ->limit(2)
                ->pluck('snap_date');

            if ($latest->count() < 2) {
                return collect();
            }

            $loanEndDate   = $latest->first();
            $loanStartDate = $latest->last();
        }

        $rows = DB::table('loan_listings as ll')
            ->joinSub(
                DB::table('loan_listings')
                    ->whereIn(DB::raw('DATE(as_at_date)'), [$loanStartDate, $loanEndDate])
                    ->whereRaw("UPPER(TRIM(COALESCE(business_segment,''))) != 'CORPORATE'")
                    ->whereRaw("(TRIM(COALESCE(loan_status, '')) = '' OR loan_status IN ('NORM', 'Normal', 'OAEM', 'SUBS', 'Watch'))")
                    ->select(DB::raw('DATE(as_at_date) AS snap_date'), 'related_account', DB::raw('MAX(id) AS max_id'))
                    ->groupBy(DB::raw('DATE(as_at_date)'), 'related_account'),
                'dedup', 'll.id', '=', 'dedup.max_id'
            )
            ->selectRaw(
                "UPPER(TRIM(COALESCE(NULLIF(TRIM(ll.branch),''), LEFT(ll.related_account, 3)))) AS branch_code,
                 MAX(COALESCE(NULLIF(TRIM(ll.branch_name),''), NULLIF(TRIM(ll.branch),''), LEFT(ll.related_account, 3))) AS branch_name,
                 ll.related_account,
                 MAX(ll.name) AS account_name,
                 SUM(CASE WHEN DATE(ll.as_at_date) = ? THEN ll.loan_book_outstanding ELSE 0 END) AS loan_open,
                 SUM(CASE WHEN DATE(ll.as_at_date) = ? THEN ll.loan_book_outstanding ELSE 0 END) AS loan_close",
                [$loanStartDate, $loanEndDate]
            )
            ->groupByRaw("UPPER(TRIM(COALESCE(NULLIF(TRIM(ll.branch),''), LEFT(ll.related_account, 3)))), ll.related_account")
            ->get()
            ->map(function ($r) {
                $r->loan_movement = round((float) $r->loan_close - (float) $r->loan_open, 2);
                return $r;
            })
            ->filter(fn ($r) => $r->loan_movement != 0);

        return $rows
            ->groupBy(fn ($r) => strtoupper(trim((string) $r->branch_code)))
            ->map(function ($branchRows, $branchCode) use ($limit) {
                $gainers = $branchRows
                    ->filter(fn ($r) => $r->loan_movement > 0)
                    ->sortByDesc(fn ($r) => $r->loan_movement)
                    ->take($limit)->values()
                    ->map(function ($r, $i) { $r->rank = $i + 1; return $r; });

                $losers = $branchRows
                    ->filter(fn ($r) => $r->loan_movement < 0)
                    ->sortBy(fn ($r) => $r->loan_movement)
                    ->take($limit)->values()
                    ->map(function ($r, $i) { $r->rank = $i + 1; return $r; });

                return [
                    'branch_name' => (string) ($branchRows->first()->branch_name ?? $branchCode),
                    'gainers'     => $gainers,
                    'losers'      => $losers,
                ];
            })
            ->filter(fn ($b) => $b['gainers']->isNotEmpty() || $b['losers']->isNotEmpty());
    }

    /**
     * Loan book per branch (open / close) keyed by branch code.
     * Uses nearest available as_at_date on or before each period date.
     * Deduplicates per (related_account, date) and excludes CORPORATE.
     *
     * @return array<string, array{open: float, close: float}>
     */
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

        // If no distinct pair found, fall back to the two most recent snapshots in the system
        if (!$loanStartDate || !$loanEndDate || $loanStartDate === $loanEndDate) {
            $latest = DB::table('loan_listings')
                ->whereNotNull('as_at_date')
                ->select(DB::raw('DATE(as_at_date) AS snap_date'))
                ->distinct()
                ->orderByDesc('snap_date')
                ->limit(2)
                ->pluck('snap_date');

            if ($latest->count() < 2) {
                return [];
            }

            $loanEndDate   = $latest->first();
            $loanStartDate = $latest->last();
        }

        $dates = array_values(array_unique(array_filter([$loanStartDate, $loanEndDate])));

        $rows = DB::table('loan_listings as ll')
            ->joinSub(
                DB::table('loan_listings')
                    ->whereIn(DB::raw('DATE(as_at_date)'), $dates)
                    ->whereRaw("UPPER(TRIM(COALESCE(business_segment,''))) != 'CORPORATE'")
                    ->whereRaw("(TRIM(COALESCE(loan_status, '')) = '' OR loan_status IN ('NORM', 'Normal', 'OAEM', 'SUBS', 'Watch'))")
                    ->select(DB::raw('DATE(as_at_date) AS snap_date'), 'related_account', DB::raw('MAX(id) AS max_id'))
                    ->groupBy(DB::raw('DATE(as_at_date)'), 'related_account'),
                'dedup',
                'll.id',
                '=',
                'dedup.max_id'
            )
            ->selectRaw(
                "UPPER(TRIM(COALESCE(NULLIF(TRIM(ll.branch),''), LEFT(ll.related_account, 3)))) AS branch_code,
                 SUM(CASE WHEN DATE(ll.as_at_date) = ? THEN ll.loan_book_outstanding ELSE 0 END) AS loan_open,
                 SUM(CASE WHEN DATE(ll.as_at_date) = ? THEN ll.loan_book_outstanding ELSE 0 END) AS loan_close",
                [$loanStartDate ?? $loanEndDate, $loanEndDate ?? $loanStartDate]
            )
            ->groupByRaw("UPPER(TRIM(COALESCE(NULLIF(TRIM(ll.branch),''), LEFT(ll.related_account, 3))))")
            ->get();

        $result = [];
        $allOpen = 0.0;
        $allClose = 0.0;

        foreach ($rows as $r) {
            $code = strtoupper(trim((string) $r->branch_code));
            if ($code === '') continue;
            $result[$code] = ['open' => (float) $r->loan_open, 'close' => (float) $r->loan_close];
            $allOpen  += (float) $r->loan_open;
            $allClose += (float) $r->loan_close;
        }

        // ALL row — total across all branches
        $result['ALL'] = ['open' => $allOpen, 'close' => $allClose];

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

        $emails = array_map(fn($e) => strtolower(trim((string) $e)), $emails);
        $emails = array_values(array_filter($emails, fn($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)));
        return array_values(array_unique($emails));
    }
}
