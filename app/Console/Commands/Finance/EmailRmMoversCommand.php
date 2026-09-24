<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Mail\RmMoversReportMail;
use App\Services\Reports\RmMoversService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class EmailRmMoversCommand extends Command
{
    protected $signature = 'reports:email-rm-movers
        {start : Start date YYYY-MM-DD (requested start)}
        {end : End date YYYY-MM-DD}
        {--to= : Override TO recipients (comma/semicolon/space separated)}
        {--cc= : Override CC recipients (comma/semicolon/space separated)}
        {--rms= : Override RM sales codes (comma/semicolon/space separated); defaults to the tracked portfolio list}
        {--drilldown=10 : Top customer gainers/losers to show across the RM list}
    ';

    protected $description = 'Email RM Movers report (deposit movement) for a fixed portfolio of RM sales codes, reading/building from rm_movers.';

    /**
     * The RM portfolio this report is currently scoped to.
     * Excludes James Kivinda Kinanga (KE1222) and Jane Nyawira (KE1296) — left the bank.
     * Includes Joan Sang (KE1343) — new RM.
     */
    private const DEFAULT_RM_CODES = [
        'KE0827' => 'Veronica Nasieku Lalarari',
        'KE1228' => 'James Chisakane Odera',
        'KE0539' => 'Lucy Kamede Lidahuli',
        'KE1189' => 'Edward Mwenda',
        'KE1330' => 'Jenipher Dola',
        'KE1285' => "Jackson Nyakang'o",
        'KE1301' => 'Susan Odhiambo',
        'KE0887' => 'John Njogu Waithaka',
        'KE1187' => 'Betty Chelagat Keter',
        'KE1318' => 'Edwin Araka',
        'KE0445' => 'Jennifer Waithera Macharia',
        'KE0949' => 'Monica Nyambura Gikonyo',
        'KE1262' => 'Glory Kendi',
        'KE0343' => 'Nancy Akoth Oywer',
        'KE1286' => 'Viginia Wangui Waweru',
        'KE1229' => 'Erick Ochieng Ouma',
        'KE1343' => 'Joan Sang',
    ];

    public function handle(RmMoversService $service): int
    {
        $requestedStart = Carbon::parse((string) $this->argument('start'))->toDateString();
        $end            = Carbon::parse((string) $this->argument('end'))->toDateString();
        $drilldownLimit = max(1, (int) $this->option('drilldown'));

        $rmCodes = $this->resolveRmCodes();

        // TO
        $toOpt = (string) ($this->option('to') ?? '');
        $to = $toOpt !== ''
            ? $this->parseEmails($toOpt)
            : $this->parseEmails(config('reports.balances.rm_movers_to', []));

        if (empty($to)) {
            $this->error('No TO recipients configured. Set reports.balances.rm_movers_to or pass --to=');
            return self::FAILURE;
        }

        // CC
        $ccOpt = (string) ($this->option('cc') ?? '');
        $cc = $ccOpt !== ''
            ? $this->parseEmails($ccOpt)
            : $this->parseEmails(config('reports.balances.rm_movers_cc', []));

        // 1) Try requested start
        $effectiveStart = $requestedStart;
        $rows = $this->fetchRows($effectiveStart, $end, $rmCodes);

        // 2) Fallback: latest start_date already built for this end_date
        if ($rows->isEmpty()) {
            $fallbackStart = DB::table('rm_movers')
                ->whereDate('end_date', $end)
                ->max('start_date');

            if ($fallbackStart) {
                $effectiveStart = Carbon::parse((string) $fallbackStart)->toDateString();
                $rows = $this->fetchRows($effectiveStart, $end, $rmCodes);
            }
        }

        // 3) Fallback: build it on the fly for the requested period
        if ($rows->isEmpty()) {
            try {
                $service->build($requestedStart, $end);
                $effectiveStart = $requestedStart;
                $rows = $this->fetchRows($effectiveStart, $end, $rmCodes);
            } catch (\Throwable $e) {
                $this->warn("Could not auto-build rm_movers for {$requestedStart} → {$end}: " . $e->getMessage());
            }
        }

        if ($rows->isEmpty()) {
            $this->warn("No rm_movers rows found for requested {$requestedStart} → {$end} (and no fallback found).");
            $this->warn("Run: php artisan reports:build-rm-movers {$requestedStart} {$end}");
            return self::FAILURE;
        }

        if ($effectiveStart !== $requestedStart) {
            $this->line("⚠ Fallback applied: using {$effectiveStart} → {$end} (requested was {$requestedStart} → {$end})");
        }

        $loanData     = $this->fetchRmLoanData($effectiveStart, $end, $rmCodes);
        $accountData  = $this->fetchAccountSnapshot($rmCodes);

        // Fill in every RM in the requested list with a zero row when it has no movement
        // (e.g. a brand new RM with no balances yet), keyed by rm_code -> known name (or the code itself).
        $rmRows = collect($rmCodes)
            ->mapWithKeys(fn ($code) => [$code => self::DEFAULT_RM_CODES[$code] ?? $code])
            ->map(function ($name, $code) use ($rows, $loanData, $accountData) {
                $row     = $rows->get($code);
                $loan    = $loanData[$code] ?? ['open' => 0.0, 'close' => 0.0];
                $account = $accountData[$code] ?? null;

                return (object) [
                    'rm_code'         => $code,
                    'rm_name'         => $name,
                    'start_balance'   => $row ? (float) $row->start_balance : 0.0,
                    'end_balance'     => $row ? (float) $row->end_balance : 0.0,
                    'movement'        => $row ? (float) $row->movement : 0.0,
                    'cif_count'       => $account ? (int) $account->total_customers : ($row ? (int) $row->cif_count : 0),
                    'total_accounts'  => $account ? (int) $account->total_accounts : 0,
                    'loan_open'       => (float) $loan['open'],
                    'loan_close'      => (float) $loan['close'],
                    'loan_movement'   => round((float) $loan['close'] - (float) $loan['open'], 2),
                ];
            })
            ->sortBy('rm_name')
            ->values();

        $totals = (object) [
            'start_balance'  => (float) $rmRows->sum('start_balance'),
            'end_balance'    => (float) $rmRows->sum('end_balance'),
            'movement'       => (float) $rmRows->sum('movement'),
            'cif_count'      => (int) $rmRows->sum('cif_count'),
            'total_accounts' => (int) $rmRows->sum('total_accounts'),
            'loan_open'      => (float) $rmRows->sum('loan_open'),
            'loan_close'     => (float) $rmRows->sum('loan_close'),
            'loan_movement'  => (float) $rmRows->sum('loan_movement'),
        ];

        $drilldown = $service->drilldownByRmCodes($effectiveStart, $end, $rmCodes, $drilldownLimit);

        $mailable = new RmMoversReportMail(
            $effectiveStart,
            $end,
            $rmRows,
            $totals,
            collect($drilldown['gainers']),
            collect($drilldown['losers'])
        );

        Mail::to($to)->cc($cc)->send($mailable);

        $this->info('RM movers email sent.');
        $this->line('TO: ' . implode(', ', $to));
        $this->line('CC: ' . (empty($cc) ? '(none)' : implode(', ', $cc)));
        $this->line("Period: {$effectiveStart} → {$end} | RMs: " . count($rmCodes));

        return self::SUCCESS;
    }

    private function fetchRows(string $start, string $end, array $rmCodes): \Illuminate\Support\Collection
    {
        return DB::table('rm_movers')
            ->whereDate('start_date', $start)
            ->whereDate('end_date', $end)
            ->whereIn('rm_code', $rmCodes)
            ->get()
            ->keyBy(fn ($r) => strtoupper(trim((string) $r->rm_code)));
    }

    /**
     * Performing loan book per RM (open / close), using nearest available as_at_date
     * on or before each period date. Mirrors EmailBranchMoversCommand::fetchBranchLoanData,
     * grouped by rm_officer instead of branch and scoped to the given RM codes.
     *
     * @return array<string, array{open: float, close: float}>
     */
    private function fetchRmLoanData(string $start, string $end, array $rmCodes): array
    {
        if (empty($rmCodes)) {
            return [];
        }

        $loanStartDate = DB::table('loan_listings')
            ->whereNotNull('as_at_date')->whereDate('as_at_date', '<=', $start)->max('as_at_date');
        $loanEndDate = DB::table('loan_listings')
            ->whereNotNull('as_at_date')->whereDate('as_at_date', '<=', $end)->max('as_at_date');

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
                    ->whereIn('rm_officer', $rmCodes)
                    ->select(DB::raw('DATE(as_at_date) AS snap_date'), 'related_account', DB::raw('MAX(id) AS max_id'))
                    ->groupBy(DB::raw('DATE(as_at_date)'), 'related_account'),
                'dedup',
                'll.id',
                '=',
                'dedup.max_id'
            )
            ->selectRaw(
                "ll.rm_officer AS rm_code,
                 SUM(CASE WHEN DATE(ll.as_at_date) = ? THEN ll.loan_book_outstanding ELSE 0 END) AS loan_open,
                 SUM(CASE WHEN DATE(ll.as_at_date) = ? THEN ll.loan_book_outstanding ELSE 0 END) AS loan_close",
                [$loanStartDate, $loanEndDate]
            )
            ->groupBy('ll.rm_officer')
            ->get();

        $result = [];
        foreach ($rows as $r) {
            $code = strtoupper(trim((string) $r->rm_code));
            if ($code === '') continue;
            $result[$code] = ['open' => (float) $r->loan_open, 'close' => (float) $r->loan_close];
        }

        return $result;
    }

    /**
     * Current portfolio snapshot per RM: distinct customers (CIF) and total accounts managed,
     * from customer_accounts_imports.acc_ofcr. Not date-scoped — this table reflects the
     * latest import, same convention as BuildRmWorkloadCommand's customer count.
     *
     * @return array<string, object{total_customers:int, total_accounts:int}>
     */
    private function fetchAccountSnapshot(array $rmCodes): array
    {
        if (empty($rmCodes)) {
            return [];
        }

        return DB::table('customer_accounts_imports')
            ->selectRaw("
                UPPER(TRIM(acc_ofcr)) AS rm_code,
                COUNT(DISTINCT TRIM(f12_cif)) AS total_customers,
                COUNT(*) AS total_accounts
            ")
            ->whereNotNull('acc_ofcr')
            ->whereRaw("TRIM(acc_ofcr) <> ''")
            ->whereRaw(
                'UPPER(TRIM(acc_ofcr)) IN (' . implode(',', array_fill(0, count($rmCodes), '?')) . ')',
                $rmCodes
            )
            ->groupBy(DB::raw('UPPER(TRIM(acc_ofcr))'))
            ->get()
            ->keyBy(fn ($r) => strtoupper(trim((string) $r->rm_code)))
            ->all();
    }

    private function resolveRmCodes(): array
    {
        $opt = (string) ($this->option('rms') ?? '');

        if (trim($opt) === '') {
            return array_keys(self::DEFAULT_RM_CODES);
        }

        return collect(preg_split('/[,\s;]+/', $opt) ?: [])
            ->map(fn ($c) => strtoupper(trim((string) $c)))
            ->filter()
            ->unique()
            ->values()
            ->all();
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
