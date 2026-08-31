<?php

declare(strict_types=1);

namespace App\Services\Finance;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Precomputes rm_performance_monthly: per-RM, per-calendar-month deposits mobilized,
 * loans disbursed (proxy) and NTB accounts opened, so /finance/rm-performance reads a
 * small indexed table instead of re-walking customer_balances/loan_listings/
 * customer_accounts_imports on every page view. Same precomputed-table shape as
 * rm_movers/rm_workload_summary — rebuilt on demand via `finance:build-rm-performance-monthly`.
 */
class RmPerformanceService
{
    private const EXCLUDED_BRANCH_CODES = ['834', '950', 'P50'];

    /**
     * Rebuilds rm_performance_monthly from scratch.
     *
     * @return array{rows: int, unparsed_value_dt: int}
     */
    public function build(): array
    {
        $depositsByRmByMonth = $this->buildDepositsByMonth();
        [$loansByRmByMonth, $unparsedCount] = $this->buildLoansByMonth();
        $ntbByRmByMonth = $this->buildNtbByMonth();

        $keys = [];
        foreach ([$depositsByRmByMonth, $loansByRmByMonth, $ntbByRmByMonth] as $source) {
            foreach ($source as $rmCode => $months) {
                foreach ($months as $key => $_) {
                    $keys[$rmCode][$key] = true;
                }
            }
        }

        $now  = now();
        $rows = [];

        foreach ($keys as $rmCode => $months) {
            foreach (array_keys($months) as $key) {
                [$year, $month] = array_map('intval', explode('-', $key));

                $deposit = $depositsByRmByMonth[$rmCode][$key] ?? null;
                $loan    = $loansByRmByMonth[$rmCode][$key] ?? null;
                $ntb     = $ntbByRmByMonth[$rmCode][$key] ?? null;

                $rows[] = [
                    'period_year'             => $year,
                    'period_month'            => $month,
                    'rm_code'                 => $rmCode,
                    'deposit_closing_balance' => $deposit['closing'] ?? 0.0,
                    'deposit_movement'        => $deposit['movement'] ?? null,
                    'balance_snapshot_date'   => $deposit['snap_date'] ?? null,
                    'loan_disbursed_proxy'    => $loan['proxy'] ?? 0.0,
                    'loan_disbursed_count'    => $loan['count'] ?? 0,
                    'loan_snapshot_date'      => $loan['snap_date'] ?? null,
                    'ntb_count'               => $ntb['total'] ?? 0,
                    'created_at'              => $now,
                    'updated_at'              => $now,
                ];
            }
        }

        DB::transaction(function () use ($rows) {
            // DELETE (not TRUNCATE) so this stays inside the transaction — TRUNCATE
            // implicitly commits in MySQL, which would leave the table empty if the
            // insert below failed partway through.
            DB::table('rm_performance_monthly')->delete();

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('rm_performance_monthly')->insert($chunk);
            }
        });

        return ['rows' => count($rows), 'unparsed_value_dt' => $unparsedCount];
    }

    /**
     * Per-RM rows for one year: monthly series + YTD totals + latest-month figures.
     *
     * @return array<string, array{
     *     rm_code: string,
     *     monthly: array,
     *     ytd_deposit_movement: float,
     *     ytd_loan_disbursed: float,
     *     ytd_ntb: int,
     *     latest_month: ?int,
     *     month_deposit_movement: ?float,
     *     month_loan_disbursed: float,
     *     month_ntb: int,
     * }>
     */
    public function forYear(int $year): array
    {
        $rows = DB::table('rm_performance_monthly')
            ->where('period_year', $year)
            ->orderBy('period_month')
            ->get();

        $byRm = [];

        foreach ($rows as $r) {
            $code = $r->rm_code;

            $byRm[$code]['rm_code'] ??= $code;
            $byRm[$code]['monthly'][] = [
                'year'                    => (int) $r->period_year,
                'month'                   => (int) $r->period_month,
                'deposit_closing_balance' => (float) $r->deposit_closing_balance,
                'deposit_movement'        => $r->deposit_movement !== null ? (float) $r->deposit_movement : null,
                'loan_disbursed_proxy'    => (float) $r->loan_disbursed_proxy,
                'loan_disbursed_count'    => (int) $r->loan_disbursed_count,
                'ntb_count'               => (int) $r->ntb_count,
                'balance_snapshot_date'   => $r->balance_snapshot_date,
                'loan_snapshot_date'      => $r->loan_snapshot_date,
            ];
        }

        foreach ($byRm as $code => &$data) {
            $monthly = collect($data['monthly'])->sortBy('month')->values();
            $data['monthly'] = $monthly->all();

            $data['ytd_deposit_movement'] = (float) $monthly->sum('deposit_movement');
            $data['ytd_loan_disbursed']   = (float) $monthly->sum('loan_disbursed_proxy');
            $data['ytd_ntb']              = (int) $monthly->sum('ntb_count');

            $latest = $monthly->sortByDesc('month')->first();

            $data['latest_month']            = $latest['month'] ?? null;
            $data['month_deposit_movement']  = $latest['deposit_movement'] ?? null;
            $data['month_loan_disbursed']    = $latest['loan_disbursed_proxy'] ?? 0.0;
            $data['month_ntb']               = $latest['ntb_count'] ?? 0;
            $data['balance_snapshot_date']   = $latest['balance_snapshot_date'] ?? null;
            $data['loan_snapshot_date']      = $latest['loan_snapshot_date'] ?? null;
        }
        unset($data);

        return $byRm;
    }

    /**
     * Single RM's last N months of history, chronological order, for the drilldown trend chart.
     */
    public function trendForRm(string $rmCode, int $months = 24): array
    {
        $rmCode = strtoupper(trim($rmCode));

        return DB::table('rm_performance_monthly')
            ->where('rm_code', $rmCode)
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->limit($months)
            ->get()
            ->sortBy(fn ($r) => $r->period_year * 100 + $r->period_month)
            ->values()
            ->map(fn ($r) => [
                'year'                 => (int) $r->period_year,
                'month'                => (int) $r->period_month,
                'deposit_movement'     => $r->deposit_movement !== null ? (float) $r->deposit_movement : null,
                'loan_disbursed_proxy' => (float) $r->loan_disbursed_proxy,
                'ntb_count'            => (int) $r->ntb_count,
            ])
            ->all();
    }

    /**
     * Deposits, per RM per calendar month: the latest balance_date within each month is
     * that month's closing snapshot (customer_balances is already pruned to one row per
     * account per month beyond a 14-day trailing window, so this matches the storage grain).
     * Movement is computed against each RM's previous *populated* month, not strictly m-1.
     *
     * @return array<string, array<string, array{closing: float, movement: ?float, snap_date: string, year: int, month: int}>>
     */
    private function buildDepositsByMonth(): array
    {
        $monthGrid = DB::table('customer_balances')
            ->selectRaw('YEAR(balance_date) as y, MONTH(balance_date) as m, MAX(balance_date) as snap_date')
            ->groupByRaw('YEAR(balance_date), MONTH(balance_date)')
            ->orderByRaw('YEAR(balance_date), MONTH(balance_date)')
            ->get();

        $closingByRmByMonth = [];

        foreach ($monthGrid as $g) {
            $rows = DB::table('customer_balances as cb')
                ->join('customer_accounts_imports as cai', 'cai.cust_ac_no', '=', 'cb.cust_ac_no')
                ->where('cb.balance_date', $g->snap_date)
                ->whereNotIn('cb.branch_code', self::EXCLUDED_BRANCH_CODES)
                ->whereNotNull('cai.acc_ofcr')
                ->where('cai.acc_ofcr', '<>', '')
                ->selectRaw('UPPER(TRIM(cai.acc_ofcr)) as rm_code, SUM(cb.lcy_balance) as total')
                ->groupByRaw('UPPER(TRIM(cai.acc_ofcr))')
                ->get();

            $key = "{$g->y}-{$g->m}";

            foreach ($rows as $r) {
                $rmCode = strtoupper(trim((string) $r->rm_code));

                $closingByRmByMonth[$rmCode][$key] = [
                    'closing'   => (float) $r->total,
                    'snap_date' => $g->snap_date,
                    'year'      => (int) $g->y,
                    'month'     => (int) $g->m,
                ];
            }
        }

        foreach ($closingByRmByMonth as $rmCode => &$months) {
            uasort($months, fn ($a, $b) => ($a['year'] * 100 + $a['month']) <=> ($b['year'] * 100 + $b['month']));

            $prevClosing = null;

            foreach ($months as $key => &$entry) {
                $entry['movement'] = $prevClosing === null ? null : $entry['closing'] - $prevClosing;
                $prevClosing       = $entry['closing'];
            }
            unset($entry);
        }
        unset($months);

        return $closingByRmByMonth;
    }

    /**
     * Loans: one pass over the latest loan_listings snapshot, bucketed by each loan's
     * value_dt (booking/value date — used as the disbursement-date proxy) and summed by
     * loan_book_outstanding (the outstanding-balance-as-disbursed-amount proxy). Every
     * loan currently on the book counts, regardless of performance status — this metric
     * is about origination, not book quality.
     *
     * @return array{0: array<string, array<string, array{proxy: float, count: int, snap_date: string, year: int, month: int}>>, 1: int}
     */
    private function buildLoansByMonth(): array
    {
        $latestLoanDate = DB::table('loan_listings')->whereNotNull('as_at_date')->max('as_at_date');

        $byRmByMonth   = [];
        $unparsedCount = 0;

        if (! $latestLoanDate) {
            return [$byRmByMonth, $unparsedCount];
        }

        $loanRows = DB::table('loan_listings as ll')
            ->joinSub(
                DB::table('loan_listings')
                    ->whereDate('as_at_date', $latestLoanDate)
                    ->select('related_account', DB::raw('MAX(id) as max_id'))
                    ->groupBy('related_account'),
                'latest',
                'll.id',
                '=',
                'latest.max_id'
            )
            ->whereNotNull('ll.rm_officer')
            ->where('ll.rm_officer', '<>', '')
            ->selectRaw("
                ll.rm_officer AS rm_code,
                ll.loan_book_outstanding,
                JSON_UNQUOTE(JSON_EXTRACT(ll.raw, '$.value_dt')) AS value_dt_raw
            ")
            ->get();

        foreach ($loanRows as $r) {
            $parsed = $this->parseLoanValueDate($r->value_dt_raw);

            if (! $parsed) {
                $unparsedCount++;
                continue;
            }

            $rmCode = strtoupper(trim((string) $r->rm_code));
            $key    = "{$parsed->year}-{$parsed->month}";

            $entry = $byRmByMonth[$rmCode][$key] ?? [
                'proxy'     => 0.0,
                'count'     => 0,
                'snap_date' => $latestLoanDate,
                'year'      => $parsed->year,
                'month'     => $parsed->month,
            ];

            $entry['proxy'] += (float) $r->loan_book_outstanding;
            $entry['count'] += 1;

            $byRmByMonth[$rmCode][$key] = $entry;
        }

        return [$byRmByMonth, $unparsedCount];
    }

    /**
     * NTB: unique CIFs whose account opened in a given calendar month, per RM.
     * ac_open_date is a real DATE column — use it directly, no STR_TO_DATE reparsing.
     *
     * @return array<string, array<string, array{total: int, year: int, month: int}>>
     */
    private function buildNtbByMonth(): array
    {
        $rows = DB::table('customer_accounts_imports')
            ->whereNotNull('acc_ofcr')
            ->whereRaw("TRIM(acc_ofcr) <> ''")
            ->whereNotNull('f12_cif')
            ->whereNotNull('ac_open_date')
            ->selectRaw('
                UPPER(TRIM(acc_ofcr)) AS rm_code,
                YEAR(ac_open_date) AS y,
                MONTH(ac_open_date) AS m,
                COUNT(DISTINCT f12_cif) AS total
            ')
            ->groupByRaw('UPPER(TRIM(acc_ofcr)), YEAR(ac_open_date), MONTH(ac_open_date)')
            ->get();

        $byRmByMonth = [];

        foreach ($rows as $r) {
            $rmCode = strtoupper(trim((string) $r->rm_code));
            $key    = "{$r->y}-{$r->m}";

            $byRmByMonth[$rmCode][$key] = [
                'total' => (int) $r->total,
                'year'  => (int) $r->y,
                'month' => (int) $r->m,
            ];
        }

        return $byRmByMonth;
    }

    /**
     * loan_listings.raw->value_dt is unnormalized Excel cell text — a date-formatted
     * cell is read via PhpSpreadsheet's getValue() (not getFormattedValue()) at import
     * time, so it commonly arrives as a numeric Excel serial rather than a date string.
     * Try the serial-number case first, then a handful of common text formats, then a
     * last-resort Carbon::parse().
     */
    private function parseLoanValueDate(?string $raw): ?Carbon
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return null;
        }

        if (is_numeric($raw) && (float) $raw > 20000 && (float) $raw < 80000) {
            try {
                return Carbon::create(1899, 12, 30)->addDays((int) round((float) $raw));
            } catch (\Throwable) {
                return null;
            }
        }

        foreach (['d-M-y', 'd-M-Y', 'd/m/Y', 'm/d/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $raw);
            } catch (\Throwable) {
                // try the next format
            }
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }
}
