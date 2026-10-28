<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DSA account opening: accounts in customer_accounts_imports whose introducer starts with
 * CN (the DSA code, e.g. CN314).
 *
 *  - Opening counts are bucketed by ac_open_date into WTD, MTD, month-on-month (current MTD
 *    + N prior full months) and week-on-week (Mon–Sun weeks, current week = WTD).
 *  - The DSA "book" is every account the DSA has ever opened: its latest deposit balance
 *    (customer_balances, by account) and the customers' performing loan book
 *    (loan_listings, by CIF — any loan held by a customer the DSA brought in).
 *
 * Staff accounts (account_class KECATF) are excluded. customer_accounts_imports is the latest
 * snapshot, so accounts closed since they were opened won't appear.
 */
final class DsaAccountOpeningService
{
    public const INTRODUCER_PREFIX = 'CN';

    /** Same deposit GL exclusion as RmMoversService. */
    private const EXCLUDED_CR_GL = '216220001';

    /**
     * @return array{
     *     asOf: string, wtdStart: string, mtdStart: string,
     *     depositDate: ?string, loanDate: ?string,
     *     months: array<string, array{label: string, start: string, end: string}>,
     *     weeks: array<string, array{label: string, start: string, end: string}>,
     *     dsaRows: Collection, totals: object, accounts: Collection
     * }
     */
    public function build(string $asOf, int $weeks = 8, int $priorMonths = 2): array
    {
        $asOfDate = Carbon::parse($asOf)->startOfDay();
        $wtdStart = $asOfDate->copy()->startOfWeek(Carbon::MONDAY);
        $mtdStart = $asOfDate->copy()->startOfMonth();

        // Month columns, oldest first; the current month runs to $asOf (MTD).
        $months = [];
        for ($i = $priorMonths; $i >= 0; $i--) {
            $start = $mtdStart->copy()->subMonthsNoOverflow($i);
            $months[$start->format('Y-m')] = [
                'label' => $start->format('M Y') . ($i === 0 ? ' (MTD)' : ''),
                'start' => $start->toDateString(),
                'end'   => $i === 0 ? $asOfDate->toDateString() : $start->copy()->endOfMonth()->toDateString(),
            ];
        }

        // Week columns (keyed by Monday), oldest first; the current week runs to $asOf (WTD).
        $weekCols = [];
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $start = $wtdStart->copy()->subWeeks($i);
            $weekCols[$start->toDateString()] = [
                'label' => 'W/c ' . $start->format('d M') . ($i === 0 ? ' (WTD)' : ''),
                'start' => $start->toDateString(),
                'end'   => $i === 0 ? $asOfDate->toDateString() : $start->copy()->addDays(6)->toDateString(),
            ];
        }

        $accounts = $this->fetchAccounts();

        [$depositDate, $deposits] = $this->depositsByAccount($accounts->pluck('account_no')->filter()->unique()->values()->all());
        [$loanDate, $loans]       = $this->loansByCif($accounts->pluck('cif')->filter()->unique()->values()->all());

        $accounts->each(function ($a) use ($deposits, $loans) {
            $a->deposit_balance = (float) ($deposits[$a->account_no] ?? 0);
            $a->cif_loans       = (float) ($loans[$a->cif] ?? 0);
        });

        $ctx = [
            'asOf'     => $asOfDate->toDateString(),
            'wtdStart' => $wtdStart->toDateString(),
            'mtdStart' => $mtdStart->toDateString(),
            'months'   => $months,
            'weeks'    => $weekCols,
        ];

        $dsaRows = $accounts
            ->groupBy('dsa_code')
            ->map(fn (Collection $rows, string $code) => $this->summarise($code, $rows, $ctx))
            ->sortBy([['mtd_accounts', 'desc'], ['deposits', 'desc'], ['dsa_code', 'asc']])
            ->values();

        return $ctx + [
            'depositDate' => $depositDate,
            'loanDate'    => $loanDate,
            'dsaRows'     => $dsaRows,
            'totals'      => tap($this->summarise('TOTAL', $accounts, $ctx), fn ($t) => $t->dsa_name = ''),
            'accounts'    => $accounts,
        ];
    }

    /**
     * Every DSA-introduced (non-staff) account, oldest first; open_date is null when it doesn't
     * parse. For CN introducers, account_initiator_name is the DSA's name (filled by
     * finance:sync-customer-accounts; blank until that has run).
     */
    private function fetchAccounts(): Collection
    {
        $initiator = Schema::hasColumn('customer_accounts_imports', 'account_initiator_name')
            ? 'TRIM(account_initiator_name)'
            : 'NULL';

        return DB::table('customer_accounts_imports')
            ->selectRaw("
                UPPER(TRIM(introducer)) AS dsa_code,
                {$initiator}            AS dsa_name,
                TRIM(f12_cif)           AS cif,
                TRIM(cust_ac_no)        AS account_no,
                TRIM(ac_desc)           AS customer_name,
                TRIM(branch_code)       AS branch_code,
                " . AccountOpenDate::sql() . " AS open_dt
            ")
            ->whereRaw('UPPER(TRIM(introducer)) LIKE ?', [self::INTRODUCER_PREFIX . '%'])
            ->whereRaw(StaffExclusion::accountSql())
            ->orderByRaw(AccountOpenDate::sql())
            ->orderBy('introducer')
            ->get()
            ->map(function ($r) {
                $open = $r->open_dt ? Carbon::parse((string) $r->open_dt) : null;

                $r->dsa_name   = (string) ($r->dsa_name ?? '');
                $r->cif        = (string) ($r->cif ?? '');
                $r->account_no = (string) ($r->account_no ?? '');
                $r->open_date  = $open?->toDateString();
                $r->week_key   = $open?->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
                $r->month_key  = $open?->format('Y-m');

                return $r;
            });
    }

    /**
     * Latest-day LCY deposit balance per account (negatives floored at 0, GL 216220001
     * excluded — same rules as RmMoversService).
     *
     * @return array{0: ?string, 1: array<string, float>} [balance_date, account_no => balance]
     */
    private function depositsByAccount(array $accountNos): array
    {
        $date = DB::table('customer_balances')->max('balance_date');
        if (! $date || empty($accountNos)) {
            return [$date, []];
        }

        $balances = [];
        foreach (array_chunk($accountNos, 1000) as $chunk) {
            DB::table('customer_balances')
                ->selectRaw('TRIM(cust_ac_no) AS account_no, SUM(GREATEST(lcy_balance, 0)) AS balance')
                ->where('balance_date', $date)
                ->whereIn('cust_ac_no', $chunk)
                ->where(fn ($q) => $q->whereNull('cr_gl')->orWhere('cr_gl', '<>', self::EXCLUDED_CR_GL))
                ->groupBy(DB::raw('TRIM(cust_ac_no)'))
                ->get()
                ->each(function ($r) use (&$balances) {
                    $balances[(string) $r->account_no] = (float) $r->balance;
                });
        }

        return [Carbon::parse((string) $date)->toDateString(), $balances];
    }

    /**
     * Performing, non-Corporate, non-staff loan book per CIF on the latest as_at_date
     * (one row per related_account, latest id) — same filters as RmLoanMoversService.
     *
     * @return array{0: ?string, 1: array<string, float>} [as_at_date, cif => outstanding]
     */
    private function loansByCif(array $cifs): array
    {
        $date = DB::table('loan_listings')->max('as_at_date');
        if (! $date || empty($cifs)) {
            return [$date, []];
        }

        $loans = [];
        foreach (array_chunk($cifs, 1000) as $chunk) {
            $dedup = DB::table('loan_listings')
                ->where('as_at_date', $date)
                ->whereIn('cif', $chunk)
                ->whereRaw(RmLoanMoversService::NON_CORPORATE_FILTER)
                ->whereRaw(RmLoanMoversService::PERFORMING_FILTER)
                ->whereRaw(RmLoanMoversService::NON_STAFF_FILTER)
                ->selectRaw('related_account, MAX(id) AS max_id')
                ->groupBy('related_account');

            DB::table('loan_listings as ll')
                ->joinSub($dedup, 'dedup', 'll.id', '=', 'dedup.max_id')
                ->selectRaw('TRIM(ll.cif) AS cif, SUM(ll.loan_book_outstanding) AS outstanding')
                ->groupBy(DB::raw('TRIM(ll.cif)'))
                ->get()
                ->each(function ($r) use (&$loans) {
                    $loans[(string) $r->cif] = (float) $r->outstanding;
                });
        }

        return [Carbon::parse((string) $date)->toDateString(), $loans];
    }

    private function summarise(string $code, Collection $rows, array $ctx): object
    {
        // Opening counts only use accounts with a parseable open date on or before $asOf.
        $dated   = $rows->filter(fn ($r) => $r->open_date !== null && $r->open_date <= $ctx['asOf']);
        $wtd     = $dated->filter(fn ($r) => $r->open_date >= $ctx['wtdStart']);
        $mtd     = $dated->filter(fn ($r) => $r->open_date >= $ctx['mtdStart']);
        $byMonth = $dated->countBy('month_key');
        $byWeek  = $dated->countBy('week_key');

        $monthKeys = array_keys($ctx['months']);
        $weekKeys  = array_keys($ctx['weeks']);

        // Loans are per customer, so count each CIF once (a CIF can hold several DSA accounts).
        $customers = $rows->filter(fn ($r) => $r->cif !== '')->unique('cif');

        return (object) [
            'dsa_code'       => $code,
            // A code's accounts can carry slightly different spellings; show the most common.
            'dsa_name'       => (string) $rows->pluck('dsa_name')->filter()->countBy()->sortDesc()->keys()->first(),
            'wtd_accounts'   => $wtd->count(),
            'wtd_customers'  => $wtd->pluck('cif')->filter()->unique()->count(),
            'mtd_accounts'   => $mtd->count(),
            'mtd_customers'  => $mtd->pluck('cif')->filter()->unique()->count(),
            'months'         => array_combine($monthKeys, array_map(fn ($k) => (int) ($byMonth[$k] ?? 0), $monthKeys)),
            'weeks'          => array_combine($weekKeys, array_map(fn ($k) => (int) ($byWeek[$k] ?? 0), $weekKeys)),
            'book_accounts'  => $rows->count(),
            'book_customers' => $customers->count(),
            'deposits'       => round((float) $rows->sum('deposit_balance'), 2),
            'loans'          => round((float) $customers->sum('cif_loans'), 2),
        ];
    }
}
