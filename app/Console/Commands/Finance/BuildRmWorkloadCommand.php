<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Precomputes the RM Workload dashboard's per-RM portfolio metrics into
 * rm_workload_summary, so /finance/rm-workload reads a small indexed table
 * instead of re-running three aggregate queries over customer_accounts_imports
 * and customer_balances (millions of rows) on every cache miss.
 *
 * Run this after any balances import, loans import, or customer_accounts_imports
 * refresh — whichever changed most recently determines what actually needs
 * recomputing, but rebuilding is cheap enough (one pass, small RM cardinality)
 * to just always rebuild the whole table.
 */
class BuildRmWorkloadCommand extends Command
{
    protected $signature = 'finance:build-rm-workload';

    protected $description = 'Rebuilds rm_workload_summary from customer_accounts_imports, customer_balances and loan_listings.';

    public function handle(): int
    {
        $hasOfficerName = Schema::hasColumn('customer_accounts_imports', 'officer_name');
        $nameSelect     = $hasOfficerName ? 'MAX(cai.officer_name)' : 'NULL';

        $latestBalanceDate = DB::table('customer_balances')->max('balance_date');
        $latestLoanDate    = DB::table('loan_listings')->whereNotNull('as_at_date')->max('as_at_date');

        $this->info("Building RM workload summary (balance_date={$latestBalanceDate}, loan_date={$latestLoanDate})...");

        // ONLY_FULL_GROUP_BY rejects the correlated subsegment fallback subquery; disable for this session only
        DB::statement("SET SESSION sql_mode = REPLACE(@@SESSION.sql_mode, 'ONLY_FULL_GROUP_BY', '')");

        // ── Query 1: RM portfolio metrics from customer_accounts_imports ───────
        // subsegment prefers the explicit relationship_managers mapping; the
        // correlated subquery only runs (per RM) as a fallback for RMs that
        // don't have one yet — CASE short-circuits it, unlike COALESCE.
        $rows = DB::table('customer_accounts_imports as cai')
            ->selectRaw("
                UPPER(TRIM(cai.acc_ofcr))                                                     AS rm_code,
                {$nameSelect}                                                                  AS officer_name,
                MAX(rm.name)                                                                  AS rm_name,
                MAX(rm.segment)                                                               AS segment,
                CASE
                    WHEN MAX(rm.subsegment) IS NOT NULL THEN MAX(rm.subsegment)
                    ELSE (
                        SELECT ssm2.business_seg_short
                        FROM customer_accounts_imports cai2
                        LEFT JOIN sub_segment_mappings ssm2 ON cai2.etibiseg2 = ssm2.mis_code
                        WHERE UPPER(TRIM(cai2.acc_ofcr)) = UPPER(TRIM(cai.acc_ofcr))
                          AND ssm2.business_seg_short IS NOT NULL
                        GROUP BY ssm2.business_seg_short
                        ORDER BY COUNT(*) DESC
                        LIMIT 1
                    )
                END                                                                            AS subsegment,
                COUNT(DISTINCT TRIM(cai.f12_cif))                                            AS cif_count,
                COUNT(*)                                                                      AS account_count,
                SUM(CASE WHEN UPPER(TRIM(cai.ac_stat_dormant)) = 'Y' THEN 1 ELSE 0 END)     AS dormant_count,
                SUM(CASE WHEN UPPER(TRIM(cai.ac_stat_dormant)) = 'N' THEN 1 ELSE 0 END)     AS active_count,
                ROUND(
                    SUM(CASE WHEN UPPER(TRIM(cai.ac_stat_dormant)) = 'Y' THEN 1 ELSE 0 END)
                    * 100.0 / NULLIF(COUNT(*), 0), 2
                )                                                                             AS dormancy_rate
            ")
            ->leftJoin('relationship_managers as rm', DB::raw('UPPER(TRIM(cai.acc_ofcr))'), '=', DB::raw('rm.rm_code'))
            ->whereNotNull('cai.acc_ofcr')
            ->whereRaw("TRIM(cai.acc_ofcr) <> ''")
            ->groupBy(DB::raw('UPPER(TRIM(cai.acc_ofcr))'))
            ->get();

        // ── Query 2: Total deposits per RM — most recent balance date only ─
        $depositsByRm = [];
        if ($latestBalanceDate) {
            DB::table('customer_balances as cb')
                ->join('customer_accounts_imports as cai3', 'cai3.cust_ac_no', '=', 'cb.cust_ac_no')
                ->where('cb.balance_date', $latestBalanceDate)
                ->whereNotIn('cb.branch_code', ['834', '950', 'P50'])
                ->whereNotNull('cai3.acc_ofcr')
                ->where('cai3.acc_ofcr', '<>', '')
                ->selectRaw('UPPER(TRIM(cai3.acc_ofcr)) AS rm_code, SUM(cb.lcy_balance) AS total_deposits')
                ->groupByRaw('UPPER(TRIM(cai3.acc_ofcr))')
                ->get()
                ->each(function ($r) use (&$depositsByRm) {
                    $depositsByRm[strtoupper(trim((string) $r->rm_code))] = (float) $r->total_deposits;
                });
        }

        // ── Query 3: Total loans per RM — deduplicated ─────────────────────────
        // Uses the generated+indexed rm_officer column instead of extracting
        // from the raw JSON column at query time.
        $loansByRm = [];
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
                ->selectRaw('ll.rm_officer AS rm_code, SUM(ll.loan_book_outstanding) AS total_loans')
                ->whereNotNull('ll.rm_officer')
                ->where('ll.rm_officer', '<>', '')
                ->groupBy('ll.rm_officer')
                ->get()
                ->each(function ($r) use (&$loansByRm) {
                    $loansByRm[strtoupper(trim((string) $r->rm_code))] = (float) $r->total_loans;
                });
        }

        // ── Merge, resolve officer name fallback, write out ─────────────────────
        $now = now();

        $summaryRows = $rows->map(function ($r) use ($depositsByRm, $loansByRm, $latestBalanceDate, $latestLoanDate, $now) {
            $officerName = $r->officer_name;
            if (empty($officerName) && !empty($r->rm_name)) {
                $officerName = $r->rm_name;
            }

            $rmCode = strtoupper(trim((string) $r->rm_code));

            return [
                'rm_code'        => $rmCode,
                'officer_name'   => $officerName,
                'segment'        => $r->segment,
                'subsegment'     => $r->subsegment,
                'cif_count'      => (int) $r->cif_count,
                'account_count'  => (int) $r->account_count,
                'dormant_count'  => (int) $r->dormant_count,
                'active_count'   => (int) $r->active_count,
                'dormancy_rate'  => (float) $r->dormancy_rate,
                'total_deposits' => $depositsByRm[$rmCode] ?? 0.0,
                'total_loans'    => $loansByRm[$rmCode] ?? 0.0,
                'balance_date'   => $latestBalanceDate,
                'loan_date'      => $latestLoanDate,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        })->values()->all();

        // DELETE (not TRUNCATE) so this stays inside the transaction — TRUNCATE
        // implicitly commits in MySQL, which would leave the table empty if the
        // insert below failed partway through.
        DB::transaction(function () use ($summaryRows) {
            DB::table('rm_workload_summary')->delete();

            foreach (array_chunk($summaryRows, 500) as $chunk) {
                DB::table('rm_workload_summary')->insert($chunk);
            }
        });

        $this->info('Rebuilt rm_workload_summary: ' . count($summaryRows) . ' RM row(s).');

        return self::SUCCESS;
    }
}
