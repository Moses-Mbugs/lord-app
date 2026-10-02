<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Per-branch figures shared by the weekly and monthly branch reports: stored deposit
 * movers (group_movers), performing-loan open/close balances and NTB counts.
 */
class BranchPeriodMetricsService
{
    /**
     * Stored BRANCH movers (built by GroupMoversService::buildBranchMovers) for an exact period.
     *
     * @return array{0: Collection, 1: Collection}  [summary rows, top gainer/loser rows]
     */
    public function groupMovers(string $start, string $end): array
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

    /**
     * Performing-loan open/close balances per branch (plus 'ALL'), using the latest
     * as_at_date on/before each of $start and $end.
     *
     * When $fallbackToLatestPair is true and those resolve to the same snapshot (or one is
     * missing), the latest two snapshots are used instead — the weekly report's behaviour.
     * Pass false when the caller needs the exact window (e.g. monthly/YTD) and would rather
     * get no data than a mislabelled one.
     *
     * @return array<string, array{open: float, close: float}>
     */
    public function loanTotals(string $start, string $end, bool $fallbackToLatestPair = true): array
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
            if (!$fallbackToLatestPair) return [];

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
     * matching every other figure in the branch reports.
     *
     * @return array<string, int>
     */
    public function ntbCounts(string $start, string $end): array
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
}
