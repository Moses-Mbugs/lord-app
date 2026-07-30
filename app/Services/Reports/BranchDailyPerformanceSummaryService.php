<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BranchDailyPerformanceSummaryService
{
    public const TABLE = 'branch_daily_performance_summaries';

    // Targets are stored in raw KES (same unit as lcy_balance).
    // The original source figures were in KES thousands, so each deposit/lending value is ×1000.
    public const TARGETS_2026 = [
        'P01' => ['name' => 'TOWERS',            'lending' => 2698348000,  'deposits' => 9436030000,  'accounts' => 3296],
        'P02' => ['name' => 'MOMBASA MOI AVENUE', 'lending' => 947479000,   'deposits' => 1496017000,  'accounts' => 2024],
        'P03' => ['name' => 'PLAZA',              'lending' => 382574000,   'deposits' => 1041071000,  'accounts' => 1673],
        'P06' => ['name' => 'THIKA',              'lending' => 780576000,   'deposits' => 718765000,   'accounts' => 1713],
        'P07' => ['name' => 'ELDORET',            'lending' => 601103000,   'deposits' => 720637000,   'accounts' => 1643],
        'P08' => ['name' => 'KISUMU',             'lending' => 500000000,   'deposits' => 1016709000,  'accounts' => 2660],
        'P09' => ['name' => 'KISII',              'lending' => 382814000,   'deposits' => 606051000,   'accounts' => 1643],
        'P11' => ['name' => 'INDUSTRIAL AREA',    'lending' => 501386000,   'deposits' => 696705000,   'accounts' => 1643],
        'P12' => ['name' => 'KARATINA',           'lending' => 78178000,    'deposits' => 166469000,   'accounts' => 602],
        'P13' => ['name' => 'WESTLANDS',          'lending' => 1328620000,  'deposits' => 4951891000,  'accounts' => 2593],
        'P15' => ['name' => 'NAKURU',             'lending' => 350000000,   'deposits' => 495996000,   'accounts' => 1673],
        'P17' => ['name' => 'NYERI',              'lending' => 250000000,   'deposits' => 690792000,   'accounts' => 1362],
        'P22' => ['name' => 'UPPER HILL',         'lending' => 999696000,   'deposits' => 2422882000,  'accounts' => 1673],
        'P23' => ['name' => 'VALLEY ARCADE',      'lending' => 400000000,   'deposits' => 2053652000,  'accounts' => 2024],
        'P24' => ['name' => 'KAREN',              'lending' => 700000000,   'deposits' => 2278225000,  'accounts' => 1673],
        'P30' => ['name' => 'FORTIS OFFICE PARK', 'lending' => 1291277000,  'deposits' => 5377898000,  'accounts' => 2593],
    ];

    public function latestBalanceDate(): ?string
    {
        $date = DB::table('customer_balances')->whereNotNull('balance_date')->max('balance_date');

        return $date ? Carbon::parse((string) $date)->toDateString() : null;
    }

    public function latestLoanAsOfDate(): ?string
    {
        $date = DB::table('loan_listings')->whereNotNull('as_at_date')->max('as_at_date');

        return $date ? Carbon::parse((string) $date)->toDateString() : null;
    }

    /**
     * Read persisted summaries for the given dates; build and persist them first if missing
     * (or incomplete), so the dashboard never depends on an app-level cache.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findOrBuild(string $balanceDate, string $loanAsOfDate): array
    {
        $existing = $this->fetchSummaries($balanceDate, $loanAsOfDate);

        if (count($existing) === count(self::TARGETS_2026)) {
            return $existing;
        }

        return $this->buildForDate($balanceDate, $loanAsOfDate);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchSummaries(string $balanceDate, string $loanAsOfDate): array
    {
        return DB::table(self::TABLE)
            ->where('balance_date', $balanceDate)
            ->where('loan_as_of_date', $loanAsOfDate)
            ->get()
            ->map(fn ($row) => $this->normalizeRow($row))
            ->values()
            ->all();
    }

    /**
     * Recompute and persist one row per branch for the given dates, then return them.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildForDate(string $balanceDate, string $loanAsOfDate): array
    {
        $branchCodes = array_keys(self::TARGETS_2026);

        // Corporate Banking MIS codes — used to exclude corporate accounts from all deposit figures.
        // etibiseg2 on customer_accounts_imports holds the MIS code per account.
        $corporateMisCodes = $this->fetchCorporateMisCodes();

        // Deposits per branch: Consumer + Commercial only (Corporate Banking excluded)
        $depositRows = $this->fetchBranchDeposits($branchCodes, $balanceDate, $corporateMisCodes);

        // MTD/YTD reference points — nearest available balance_date on or before the anchor.
        $mtdAnchor = Carbon::parse($balanceDate)->startOfMonth()->subDay()->toDateString();
        $ytdAnchor = Carbon::parse($balanceDate)->startOfYear()->subDay()->toDateString();

        $mtdDate = DB::table('customer_balances')->whereDate('balance_date', '<=', $mtdAnchor)->max('balance_date');
        $ytdDate = DB::table('customer_balances')->whereDate('balance_date', '<=', $ytdAnchor)->max('balance_date');

        $mtdDate = $mtdDate ? Carbon::parse($mtdDate)->toDateString() : null;
        $ytdDate = $ytdDate ? Carbon::parse($ytdDate)->toDateString() : null;

        $mtdDepositRows = $mtdDate ? $this->fetchBranchDeposits($branchCodes, $mtdDate, $corporateMisCodes) : collect();
        $ytdDepositRows = $ytdDate ? $this->fetchBranchDeposits($branchCodes, $ytdDate, $corporateMisCodes) : collect();

        // Currency mix (LCY/FCY) and deposit mix (current/savings/term via cr_gl) per branch.
        $mixRows = $this->fetchBranchDepositMix($branchCodes, $balanceDate, $corporateMisCodes);

        // Account breakdown: total CIFs, total accounts, and dormancy per branch (Consumer + Commercial only).
        $accountBreakdownRows = $this->fetchAccountBreakdown($branchCodes, $corporateMisCodes);

        // NTB: unique CIFs whose account was opened from 01-Jan-2026 onwards
        // ac_open_date is stored as DD-Mon-YY (e.g. 22-Mar-23)
        $accountRows = DB::table('customer_accounts_imports')
            ->whereIn('branch_code', $branchCodes)
            ->whereNotNull('f12_cif')
            ->whereNotNull('ac_open_date')
            ->whereRaw("STR_TO_DATE(ac_open_date, '%d-%b-%y') >= '2026-01-01'")
            ->select('branch_code', DB::raw('COUNT(DISTINCT f12_cif) as total_accounts'))
            ->groupBy('branch_code')
            ->get()
            ->keyBy('branch_code');

        // Loan MTD/YTD reference points — nearest available as_at_date on or before the anchor.
        $loanMtdAnchor = Carbon::parse($loanAsOfDate)->startOfMonth()->subDay()->toDateString();
        $loanYtdAnchor = Carbon::parse($loanAsOfDate)->startOfYear()->subDay()->toDateString();

        $mtdLoanDate = DB::table('loan_listings')->whereDate('as_at_date', '<=', $loanMtdAnchor)->max('as_at_date');
        $ytdLoanDate = DB::table('loan_listings')->whereDate('as_at_date', '<=', $loanYtdAnchor)->max('as_at_date');

        $mtdLoanDate = $mtdLoanDate ? Carbon::parse($mtdLoanDate)->toDateString() : null;
        $ytdLoanDate = $ytdLoanDate ? Carbon::parse($ytdLoanDate)->toDateString() : null;

        // Actual loans per branch for all three reference dates in a single pass over
        // loan_listings (one query instead of three) — the branch-derivation expression
        // it filters on has no supporting index, so each separate call was a full scan.
        $loanBuckets = $this->fetchBranchLoansForDates($branchCodes, array_filter([
            'main' => $loanAsOfDate,
            'mtd'  => $mtdLoanDate,
            'ytd'  => $ytdLoanDate,
        ]));

        $loanRows    = $loanBuckets->get('main') ?? collect();
        $mtdLoanRows = $loanBuckets->get('mtd') ?? collect();
        $ytdLoanRows = $loanBuckets->get('ytd') ?? collect();

        foreach (self::TARGETS_2026 as $code => $target) {
            $actualDeposits = (float) ($depositRows[$code]?->total_deposits ?? 0);
            $actualAccounts = (int)   ($accountRows[$code]?->total_accounts ?? 0);
            $actualLending  = (float) ($loanRows[$code]?->actual_lending   ?? 0);

            $depositPct = $target['deposits'] > 0
                ? min(100, round($actualDeposits / $target['deposits'] * 100, 1))
                : 0.0;

            $accountPct = $target['accounts'] > 0
                ? min(100, round($actualAccounts / $target['accounts'] * 100, 1))
                : 0.0;

            $lendingPct = $target['lending'] > 0
                ? min(100, round($actualLending / $target['lending'] * 100, 1))
                : 0.0;

            $ldrPct = $actualDeposits > 0 ? round($actualLending / $actualDeposits * 100, 1) : 0.0;

            $mix = $mixRows[$code] ?? null;
            $lcyAmount = round((float) ($mix->lcy_amount ?? 0), 2);
            $fcyAmount = round((float) ($mix->fcy_amount ?? 0), 2);
            $currencyTotal = $lcyAmount + $fcyAmount;

            $currentAmount = round((float) ($mix->current_amount ?? 0), 2);
            $savingsAmount = round((float) ($mix->savings_amount ?? 0), 2);
            $termAmount = round((float) ($mix->term_amount ?? 0), 2);
            $depositMixTotal = $currentAmount + $savingsAmount + $termAmount;

            $currentPct = $depositMixTotal > 0 ? round($currentAmount / $depositMixTotal * 100, 1) : 0.0;
            $savingsPct = $depositMixTotal > 0 ? round($savingsAmount / $depositMixTotal * 100, 1) : 0.0;
            $termPct = $depositMixTotal > 0 ? round($termAmount / $depositMixTotal * 100, 1) : 0.0;

            $mtdStartDeposits = (float) ($mtdDepositRows[$code]?->total_deposits ?? 0);
            $ytdStartDeposits = (float) ($ytdDepositRows[$code]?->total_deposits ?? 0);

            $mtdStartLending = (float) ($mtdLoanRows[$code]?->actual_lending ?? 0);
            $ytdStartLending = (float) ($ytdLoanRows[$code]?->actual_lending ?? 0);

            $accountBreakdown = $accountBreakdownRows[$code] ?? null;
            $totalCifs = (int) ($accountBreakdown?->total_cifs ?? 0);
            $totalAccounts = (int) ($accountBreakdown?->total_accounts ?? 0);
            $dormantAccounts = (int) ($accountBreakdown?->dormant_accounts ?? 0);
            $dormancyRate = (float) ($accountBreakdown?->dormancy_rate ?? 0);

            $this->persist([
                'balance_date'       => $balanceDate,
                'loan_as_of_date'    => $loanAsOfDate,
                'branch_code'        => $code,
                'branch_name'        => $target['name'],
                'target_deposits'    => $target['deposits'],
                'target_accounts'    => $target['accounts'],
                'target_lending'     => $target['lending'],
                'actual_deposits'    => $actualDeposits,
                'actual_accounts'    => $actualAccounts,
                'actual_lending'     => $actualLending,
                'deposit_pct'        => $depositPct,
                'account_pct'        => $accountPct,
                'lending_pct'        => $lendingPct,
                'ldr_pct'            => $ldrPct,
                'lcy_amount'         => $lcyAmount,
                'fcy_amount'         => $fcyAmount,
                'lcy_pct'            => $currencyTotal > 0 ? round($lcyAmount / $currencyTotal * 100, 1) : 0.0,
                'fcy_pct'            => $currencyTotal > 0 ? round($fcyAmount / $currencyTotal * 100, 1) : 0.0,
                'current_amount'     => $currentAmount,
                'savings_amount'     => $savingsAmount,
                'term_amount'        => $termAmount,
                'current_pct'        => $currentPct,
                'savings_pct'        => $savingsPct,
                'term_pct'           => $termPct,
                'casa_amount'        => round($currentAmount + $savingsAmount, 2),
                'casa_pct'           => round($currentPct + $savingsPct, 1),
                'mtd_movement'       => $mtdDate ? round($actualDeposits - $mtdStartDeposits, 2) : null,
                'ytd_movement'       => $ytdDate ? round($actualDeposits - $ytdStartDeposits, 2) : null,
                'mtd_reference_date' => $mtdDate,
                'ytd_reference_date' => $ytdDate,
                'total_cifs'         => $totalCifs,
                'total_accounts'     => $totalAccounts,
                'dormant_accounts'   => $dormantAccounts,
                'dormancy_rate'      => $dormancyRate,
                'mtd_loan_movement'       => $mtdLoanDate ? round($actualLending - $mtdStartLending, 2) : null,
                'ytd_loan_movement'       => $ytdLoanDate ? round($actualLending - $ytdStartLending, 2) : null,
                'mtd_loan_reference_date' => $mtdLoanDate,
                'ytd_loan_reference_date' => $ytdLoanDate,
                'last_built_at'      => now(),
            ]);
        }

        return $this->fetchSummaries($balanceDate, $loanAsOfDate);
    }

    private function persist(array $payload): void
    {
        DB::table(self::TABLE)->updateOrInsert(
            [
                'balance_date'    => $payload['balance_date'],
                'loan_as_of_date' => $payload['loan_as_of_date'],
                'branch_code'     => $payload['branch_code'],
            ],
            $payload + ['updated_at' => now(), 'created_at' => now()]
        );
    }

    private function normalizeRow(object $row): array
    {
        return [
            'code'               => $row->branch_code,
            'name'               => $row->branch_name,
            'target_deposits'    => (float) $row->target_deposits,
            'target_accounts'    => (int) $row->target_accounts,
            'target_lending'     => (float) $row->target_lending,
            'actual_deposits'    => (float) $row->actual_deposits,
            'actual_accounts'    => (int) $row->actual_accounts,
            'actual_lending'     => (float) $row->actual_lending,
            'deposit_pct'        => (float) $row->deposit_pct,
            'account_pct'        => (float) $row->account_pct,
            'lending_pct'        => (float) $row->lending_pct,
            'ldr_pct'            => (float) $row->ldr_pct,
            'lcy_amount'         => (float) $row->lcy_amount,
            'fcy_amount'         => (float) $row->fcy_amount,
            'lcy_pct'            => (float) $row->lcy_pct,
            'fcy_pct'            => (float) $row->fcy_pct,
            'current_amount'     => (float) $row->current_amount,
            'savings_amount'     => (float) $row->savings_amount,
            'term_amount'        => (float) $row->term_amount,
            'current_pct'        => (float) $row->current_pct,
            'savings_pct'        => (float) $row->savings_pct,
            'term_pct'           => (float) $row->term_pct,
            'casa_amount'        => (float) $row->casa_amount,
            'casa_pct'           => (float) $row->casa_pct,
            'mtd_movement'       => $row->mtd_movement !== null ? (float) $row->mtd_movement : null,
            'ytd_movement'       => $row->ytd_movement !== null ? (float) $row->ytd_movement : null,
            'mtd_reference_date' => $row->mtd_reference_date,
            'ytd_reference_date' => $row->ytd_reference_date,
            'total_cifs'         => (int) $row->total_cifs,
            'total_accounts'     => (int) $row->total_accounts,
            'dormant_accounts'   => (int) $row->dormant_accounts,
            'dormancy_rate'      => (float) $row->dormancy_rate,
            'mtd_loan_movement'       => $row->mtd_loan_movement !== null ? (float) $row->mtd_loan_movement : null,
            'ytd_loan_movement'       => $row->ytd_loan_movement !== null ? (float) $row->ytd_loan_movement : null,
            'mtd_loan_reference_date' => $row->mtd_loan_reference_date,
            'ytd_loan_reference_date' => $row->ytd_loan_reference_date,
        ];
    }

    /**
     * Top N customers by deposit balance per branch, Consumer + Commercial only.
     * Computed live (not persisted) since it is only needed for display.
     *
     * @return Collection<string, array<int, array{cif: string, name: string, balance: float}>>
     */
    public function topCustomersByBranch(string $balanceDate, int $limit = 5): Collection
    {
        $branchCodes = array_keys(self::TARGETS_2026);
        $corporateMisCodes = $this->fetchCorporateMisCodes();

        $query = DB::table('customer_balances')
            ->whereDate('balance_date', $balanceDate)
            ->whereIn('branch_code', $branchCodes)
            ->whereNotNull('cif')
            ->select(
                'branch_code',
                'cif',
                DB::raw('MAX(customer_name) as customer_name'),
                DB::raw('SUM(lcy_balance) as total_balance')
            )
            ->groupBy('branch_code', 'cif');

        if (!empty($corporateMisCodes)) {
            $query->whereNotIn('cif', function ($sub) use ($corporateMisCodes) {
                $sub->from('customer_accounts_imports')
                    ->select('f12_cif')
                    ->whereNotNull('f12_cif')
                    ->whereIn(DB::raw('UPPER(TRIM(etibiseg2))'), $corporateMisCodes)
                    ->distinct();
            });
        }

        return $query->get()
            ->groupBy('branch_code')
            ->map(fn (Collection $group) => $group->sortByDesc('total_balance')
                ->take($limit)
                ->values()
                ->map(fn ($row) => [
                    'cif'     => $row->cif,
                    'name'    => $row->customer_name ?: ('CIF ' . $row->cif),
                    'balance' => (float) $row->total_balance,
                ])
                ->all());
    }

    /**
     * Top N customers by loan outstanding per branch, Consumer + Commercial only.
     * Mirrors the branch loan aggregation in buildForDate() (same dedup/exclusion rules)
     * but grouped down to customer level. Computed live, not persisted.
     *
     * @return Collection<string, array<int, array{cif: string, name: string, balance: float}>>
     */
    public function topLoanCustomersByBranch(string $loanAsOfDate, int $limit = 5): Collection
    {
        $branchCodes = array_keys(self::TARGETS_2026);

        $rows = DB::table('loan_listings as ll')
            ->joinSub(
                DB::table('loan_listings')
                    ->whereDate('as_at_date', $loanAsOfDate)
                    ->whereNotNull('cif')
                    ->whereIn(DB::raw("UPPER(TRIM(COALESCE(NULLIF(TRIM(branch),''), LEFT(related_account, 3))))"), $branchCodes)
                    ->whereRaw("UPPER(TRIM(COALESCE(business_segment,''))) != 'CORPORATE'")
                    ->whereRaw("(TRIM(COALESCE(loan_status, '')) = '' OR loan_status IN ('NORM', 'Normal', 'OAEM', 'SUBS', 'Watch'))")
                    ->select('related_account', DB::raw('MAX(id) as max_id'))
                    ->groupBy('related_account'),
                'latest',
                'll.id',
                '=',
                'latest.max_id'
            )
            ->selectRaw("
                UPPER(TRIM(COALESCE(NULLIF(TRIM(ll.branch),''), LEFT(ll.related_account, 3)))) AS branch_code,
                ll.cif,
                MAX(ll.name) as customer_name,
                SUM(ll.loan_book_outstanding) as total_outstanding
            ")
            ->groupByRaw("UPPER(TRIM(COALESCE(NULLIF(TRIM(ll.branch),''), LEFT(ll.related_account, 3)))), ll.cif")
            ->get();

        return $rows->groupBy('branch_code')
            ->map(fn (Collection $group) => $group->sortByDesc('total_outstanding')
                ->take($limit)
                ->values()
                ->map(fn ($row) => [
                    'cif'     => $row->cif,
                    'name'    => $row->customer_name ?: ('CIF ' . $row->cif),
                    'balance' => (float) $row->total_outstanding,
                ])
                ->all());
    }

    /**
     * Total CIFs, total accounts, and dormancy per branch, Consumer + Commercial only.
     * etibiseg2 on customer_accounts_imports holds the MIS code per account.
     */
    private function fetchAccountBreakdown(array $branchCodes, array $corporateMisCodes): Collection
    {
        $query = DB::table('customer_accounts_imports')
            ->whereIn('branch_code', $branchCodes)
            ->whereNotNull('f12_cif')
            ->selectRaw("
                branch_code,
                COUNT(DISTINCT f12_cif) AS total_cifs,
                COUNT(*) AS total_accounts,
                SUM(CASE WHEN UPPER(TRIM(COALESCE(ac_stat_dormant, ''))) = 'Y' THEN 1 ELSE 0 END) AS dormant_accounts,
                ROUND(
                    SUM(CASE WHEN UPPER(TRIM(COALESCE(ac_stat_dormant, ''))) = 'Y' THEN 1 ELSE 0 END)
                    * 100.0 / NULLIF(COUNT(*), 0), 2
                ) AS dormancy_rate
            ")
            ->groupBy('branch_code');

        if (!empty($corporateMisCodes)) {
            $query->whereNotIn(DB::raw('UPPER(TRIM(etibiseg2))'), $corporateMisCodes);
        }

        return $query->get()->keyBy('branch_code');
    }

    private function fetchCorporateMisCodes(): array
    {
        return DB::table('sub_segment_mappings')
            ->whereRaw("LOWER(TRIM(COALESCE(business, ''))) = 'corporate banking'")
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->pluck('mis_code')
            ->filter()
            ->map(fn ($code) => strtoupper(trim((string) $code)))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Sum of lcy_balance per branch for a given balance_date, Consumer + Commercial only.
     */
    private function fetchBranchDeposits(array $branchCodes, string $date, array $corporateMisCodes): Collection
    {
        $query = DB::table('customer_balances')
            ->whereDate('balance_date', $date)
            ->whereIn('branch_code', $branchCodes)
            ->select('branch_code', DB::raw('SUM(lcy_balance) as total_deposits'))
            ->groupBy('branch_code');

        if (!empty($corporateMisCodes)) {
            $query->where(function ($q) use ($corporateMisCodes) {
                $q->whereNull('cif')
                  ->orWhereNotIn('cif', function ($sub) use ($corporateMisCodes) {
                      $sub->from('customer_accounts_imports')
                          ->select('f12_cif')
                          ->whereNotNull('f12_cif')
                          ->whereIn(DB::raw('UPPER(TRIM(etibiseg2))'), $corporateMisCodes)
                          ->distinct();
                  });
            });
        }

        return $query->get()->keyBy('branch_code');
    }

    /**
     * Actual loan book outstanding per branch for each of the given as_at_dates, Consumer +
     * Commercial only, in a single query. Deduplicates by (as_at_date, related_account) to
     * handle re-imports on the same date. Branch is derived via
     * COALESCE(branch, LEFT(related_account,3)) to handle blank branch values.
     *
     * @param array<string, string> $datesByLabel e.g. ['main' => '2026-07-28', 'mtd' => '2026-06-30']
     * @return Collection<string, Collection<string, object>> keyed by the same labels, each a
     *         Collection of rows keyed by branch_code (same shape the old per-date fetch returned)
     */
    private function fetchBranchLoansForDates(array $datesByLabel): Collection
    {
        if (empty($datesByLabel)) {
            return collect();
        }

        $branchCodes = array_keys(self::TARGETS_2026);
        $dateList = array_values(array_unique($datesByLabel));

        $rows = DB::table('loan_listings as ll')
            ->joinSub(
                DB::table('loan_listings')
                    ->whereIn('as_at_date', $dateList)
                    ->whereIn(DB::raw("UPPER(TRIM(COALESCE(NULLIF(TRIM(branch),''), LEFT(related_account, 3))))"), $branchCodes)
                    ->whereRaw("UPPER(TRIM(COALESCE(business_segment,''))) != 'CORPORATE'")
                    ->whereRaw("(TRIM(COALESCE(loan_status, '')) = '' OR loan_status IN ('NORM', 'Normal', 'OAEM', 'SUBS', 'Watch'))")
                    ->select('as_at_date', 'related_account', DB::raw('MAX(id) as max_id'))
                    ->groupBy('as_at_date', 'related_account'),
                'latest',
                'll.id',
                '=',
                'latest.max_id'
            )
            ->selectRaw("
                ll.as_at_date,
                UPPER(TRIM(COALESCE(NULLIF(TRIM(ll.branch),''), LEFT(ll.related_account, 3)))) AS branch_code,
                SUM(ll.loan_book_outstanding) as actual_lending
            ")
            ->groupByRaw("ll.as_at_date, UPPER(TRIM(COALESCE(NULLIF(TRIM(ll.branch),''), LEFT(ll.related_account, 3))))")
            ->get()
            ->groupBy(fn ($row) => Carbon::parse($row->as_at_date)->toDateString());

        $byLabel = [];
        foreach ($datesByLabel as $label => $date) {
            $byLabel[$label] = ($rows->get($date) ?? collect())->keyBy('branch_code');
        }

        return collect($byLabel);
    }

    /**
     * Currency mix (LCY/FCY) and deposit-type mix (current/savings/term via cr_gl) per branch.
     * GL 211 current, 212 savings, 213 term deposits — same convention as FinanceDailyMixSummaryService.
     */
    private function fetchBranchDepositMix(array $branchCodes, string $date, array $corporateMisCodes): Collection
    {
        $query = DB::table('customer_balances')
            ->whereDate('balance_date', $date)
            ->whereIn('branch_code', $branchCodes)
            ->selectRaw("
                branch_code,
                COALESCE(SUM(CASE WHEN UPPER(TRIM(COALESCE(currency, ''))) = 'KES' THEN lcy_balance ELSE 0 END), 0) AS lcy_amount,
                COALESCE(SUM(CASE WHEN UPPER(TRIM(COALESCE(currency, ''))) <> 'KES' THEN lcy_balance ELSE 0 END), 0) AS fcy_amount,
                COALESCE(SUM(CASE WHEN TRIM(COALESCE(cr_gl, '')) LIKE '211%' THEN lcy_balance ELSE 0 END), 0) AS current_amount,
                COALESCE(SUM(CASE WHEN TRIM(COALESCE(cr_gl, '')) LIKE '212%' THEN lcy_balance ELSE 0 END), 0) AS savings_amount,
                COALESCE(SUM(CASE WHEN TRIM(COALESCE(cr_gl, '')) LIKE '213%' THEN lcy_balance ELSE 0 END), 0) AS term_amount
            ")
            ->groupBy('branch_code');

        if (!empty($corporateMisCodes)) {
            $query->where(function ($q) use ($corporateMisCodes) {
                $q->whereNull('cif')
                  ->orWhereNotIn('cif', function ($sub) use ($corporateMisCodes) {
                      $sub->from('customer_accounts_imports')
                          ->select('f12_cif')
                          ->whereNotNull('f12_cif')
                          ->whereIn(DB::raw('UPPER(TRIM(etibiseg2))'), $corporateMisCodes)
                          ->distinct();
                  });
            });
        }

        return $query->get()->keyBy('branch_code');
    }
}
