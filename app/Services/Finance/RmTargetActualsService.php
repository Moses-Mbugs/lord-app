<?php

declare(strict_types=1);

namespace App\Services\Finance;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RmTargetActualsService
{
    /**
     * Per-RM actuals for a given year: deposits (latest balance snapshot), loans (latest
     * loan listing snapshot), and NTB (unique CIFs whose account opened during the year).
     *
     * @return array<string, array{actual_deposits: float, actual_loans: float, actual_ntb: int}>
     */
    public function forYear(int $year): array
    {
        $latestBalanceDate = DB::table('customer_balances')->max('balance_date');
        $latestLoanDate     = DB::table('loan_listings')->whereNotNull('as_at_date')->max('as_at_date');

        $cacheKey = "rm_target_actuals_{$year}_" . ($latestBalanceDate ?? 'nodata') . '_' . ($latestLoanDate ?? 'noloan');

        return Cache::remember($cacheKey, now()->addHour(), function () use ($year, $latestBalanceDate, $latestLoanDate) {
            $actuals = [];

            // ── Deposits per RM — most recent balance date, joined on cust_ac_no ──────
            if ($latestBalanceDate) {
                DB::table('customer_balances as cb')
                    ->join('customer_accounts_imports as cai', 'cai.cust_ac_no', '=', 'cb.cust_ac_no')
                    ->where('cb.balance_date', $latestBalanceDate)
                    ->whereNotIn('cb.branch_code', ['834', '950', 'P50'])
                    ->whereNotNull('cai.acc_ofcr')
                    ->where('cai.acc_ofcr', '<>', '')
                    ->selectRaw('UPPER(TRIM(cai.acc_ofcr)) AS rm_code, SUM(cb.lcy_balance) AS total')
                    ->groupByRaw('UPPER(TRIM(cai.acc_ofcr))')
                    ->get()
                    ->each(function ($r) use (&$actuals) {
                        $code = strtoupper(trim((string) $r->rm_code));
                        $actuals[$code]['actual_deposits'] = (float) $r->total;
                    });
            }

            // ── Loans per RM — latest snapshot, deduped per related_account ───────────
            // Unlike the branch dashboard, Corporate is NOT excluded here: corporate RMs
            // need their loan book reflected against their targets too.
            if ($latestLoanDate) {
                DB::table('loan_listings as ll')
                    ->joinSub(
                        DB::table('loan_listings')
                            ->whereDate('as_at_date', $latestLoanDate)
                            ->whereRaw("(TRIM(COALESCE(loan_status, '')) = '' OR loan_status IN ('NORM', 'Normal', 'OAEM', 'SUBS', 'Watch'))")
                            ->select('related_account', DB::raw('MAX(id) as max_id'))
                            ->groupBy('related_account'),
                        'latest',
                        'll.id',
                        '=',
                        'latest.max_id'
                    )
                    ->selectRaw("
                        UPPER(TRIM(JSON_UNQUOTE(JSON_EXTRACT(ll.raw, '$.rm_officer')))) AS rm_code,
                        SUM(ll.loan_book_outstanding) AS total
                    ")
                    ->whereNotNull(DB::raw("JSON_EXTRACT(ll.raw, '$.rm_officer')"))
                    ->whereRaw("TRIM(JSON_UNQUOTE(JSON_EXTRACT(ll.raw, '$.rm_officer'))) <> ''")
                    ->groupByRaw("UPPER(TRIM(JSON_UNQUOTE(JSON_EXTRACT(ll.raw, '$.rm_officer'))))")
                    ->get()
                    ->each(function ($r) use (&$actuals) {
                        $code = strtoupper(trim((string) $r->rm_code));
                        $actuals[$code]['actual_loans'] = (float) $r->total;
                    });
            }

            // ── NTB per RM — unique CIFs whose account opened during the given year ────
            // ac_open_date is a real DATE column (see the customer_accounts_imports migration
            // and the model's cast) — compare it directly, no STR_TO_DATE reparsing needed.
            DB::table('customer_accounts_imports')
                ->whereNotNull('acc_ofcr')
                ->whereRaw("TRIM(acc_ofcr) <> ''")
                ->whereNotNull('f12_cif')
                ->whereNotNull('ac_open_date')
                ->where('ac_open_date', '>=', "{$year}-01-01")
                ->where('ac_open_date', '<', ($year + 1) . '-01-01')
                ->selectRaw('UPPER(TRIM(acc_ofcr)) AS rm_code, COUNT(DISTINCT f12_cif) AS total')
                ->groupByRaw('UPPER(TRIM(acc_ofcr))')
                ->get()
                ->each(function ($r) use (&$actuals) {
                    $code = strtoupper(trim((string) $r->rm_code));
                    $actuals[$code]['actual_ntb'] = (int) $r->total;
                });

            foreach ($actuals as &$row) {
                $row['actual_deposits'] = $row['actual_deposits'] ?? 0.0;
                $row['actual_loans']    = $row['actual_loans']    ?? 0.0;
                $row['actual_ntb']      = $row['actual_ntb']      ?? 0;
            }
            unset($row);

            return $actuals;
        });
    }
}
