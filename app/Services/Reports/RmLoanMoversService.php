<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Loan-book movement per RM, sourced from loan_listings.rm_officer.
 *
 * Shared by the RM Movers (deposits+loans) daily/weekly commands and the dedicated
 * RM Loan Movers daily/weekly commands, so the exclusion rules below apply everywhere
 * loan figures are attributed to an RM in this app — not just in one report.
 *
 * Exclusions:
 * - business_segment = CORPORATE (matches every other loan report in this codebase)
 * - loan_status outside the performing book (NORM/Normal/OAEM/SUBS/Watch, or blank)
 * - linecode containing "STAFF" anywhere (case-insensitive) — staff loans, per instruction.
 */
class RmLoanMoversService
{
    private const NON_CORPORATE_FILTER = "UPPER(TRIM(COALESCE(business_segment,''))) != 'CORPORATE'";
    private const PERFORMING_FILTER    = "(TRIM(COALESCE(loan_status, '')) = '' OR loan_status IN ('NORM', 'Normal', 'OAEM', 'SUBS', 'Watch'))";
    private const NON_STAFF_FILTER     = "UPPER(COALESCE(linecode, '')) NOT LIKE '%STAFF%'";

    /**
     * Performing, non-Corporate, non-staff loan book per RM (open / close), using the
     * nearest available as_at_date on or before each period date.
     *
     * @return array<string, array{open: float, close: float}>
     */
    public function loanBookPerRm(string $start, string $end, array $rmCodes): array
    {
        if (empty($rmCodes)) {
            return [];
        }

        $dates = $this->resolveSnapshotDates($start, $end);
        if (!$dates) {
            return [];
        }
        [$loanStartDate, $loanEndDate] = $dates;

        $rows = DB::table('loan_listings as ll')
            ->joinSub(
                $this->dedupSubquery([$loanStartDate, $loanEndDate], $rmCodes),
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
     * Current snapshot: distinct performing, non-Corporate, non-staff loan accounts (and
     * distinct customers) managed per RM, as of the latest available as_at_date.
     *
     * @return array<string, array{account_count: int, customer_count: int}>
     */
    public function loanAccountSnapshotPerRm(array $rmCodes): array
    {
        if (empty($rmCodes)) {
            return [];
        }

        $latestDate = DB::table('loan_listings')->whereNotNull('as_at_date')->max('as_at_date');
        if (!$latestDate) {
            return [];
        }

        $rows = DB::table('loan_listings as ll')
            ->joinSub(
                $this->dedupSubquery([$latestDate], $rmCodes),
                'dedup',
                'll.id',
                '=',
                'dedup.max_id'
            )
            ->selectRaw('ll.rm_officer AS rm_code, COUNT(DISTINCT ll.related_account) AS account_count, COUNT(DISTINCT ll.cif) AS customer_count')
            ->groupBy('ll.rm_officer')
            ->get();

        $result = [];
        foreach ($rows as $r) {
            $code = strtoupper(trim((string) $r->rm_code));
            if ($code === '') continue;
            $result[$code] = ['account_count' => (int) $r->account_count, 'customer_count' => (int) $r->customer_count];
        }

        return $result;
    }

    /**
     * Top N loan-account gainers/losers per RM (not merged across RMs) for the given period.
     *
     * @return array<string, array{gainers: array, losers: array}> keyed by rm_code, each
     *         account row shaped as ['account' => string, 'name' => string, 'movement' => float]
     */
    public function accountMoversGroupedByRmCodes(string $start, string $end, array $rmCodes, int $limit = 10): array
    {
        if (empty($rmCodes)) {
            return [];
        }

        $limit = max(1, min($limit, 1000));

        $dates = $this->resolveSnapshotDates($start, $end);
        if (!$dates) {
            return [];
        }
        [$loanStartDate, $loanEndDate] = $dates;

        $rows = DB::table('loan_listings as ll')
            ->joinSub(
                $this->dedupSubquery([$loanStartDate, $loanEndDate], $rmCodes),
                'dedup',
                'll.id',
                '=',
                'dedup.max_id'
            )
            ->selectRaw(
                "ll.rm_officer AS rm_code,
                 ll.related_account,
                 MAX(ll.name) AS account_name,
                 SUM(CASE WHEN DATE(ll.as_at_date) = ? THEN ll.loan_book_outstanding ELSE 0 END) AS loan_open,
                 SUM(CASE WHEN DATE(ll.as_at_date) = ? THEN ll.loan_book_outstanding ELSE 0 END) AS loan_close",
                [$loanStartDate, $loanEndDate]
            )
            ->groupBy('ll.rm_officer', 'll.related_account')
            ->get()
            ->map(function ($r) {
                $r->rm_code  = strtoupper(trim((string) $r->rm_code));
                $r->movement = round((float) $r->loan_close - (float) $r->loan_open, 2);
                return $r;
            })
            ->filter(fn ($r) => $r->rm_code !== '' && $r->movement != 0)
            ->groupBy('rm_code');

        $result = [];
        foreach ($rmCodes as $code) {
            $items = collect($rows->get($code, []));

            $shape = fn ($r) => [
                'account'  => (string) $r->related_account,
                'name'     => (string) ($r->account_name ?? ''),
                'movement' => (float) $r->movement,
            ];

            $result[$code] = [
                'gainers' => $items->filter(fn ($r) => $r->movement > 0)
                    ->sortByDesc(fn ($r) => $r->movement)
                    ->take($limit)->values()->map($shape)->all(),
                'losers' => $items->filter(fn ($r) => $r->movement < 0)
                    ->sortBy(fn ($r) => $r->movement)
                    ->take($limit)->values()->map($shape)->all(),
            ];
        }

        return $result;
    }

    /**
     * Resolves the actual loan snapshot dates to compare: the nearest as_at_date on or
     * before each requested boundary, falling back to the two most recent snapshots in
     * the system if the requested period doesn't bracket two distinct snapshots. Returns
     * null if fewer than two snapshots exist at all.
     *
     * @return array{0: string, 1: string}|null [loanStartDate, loanEndDate]
     */
    private function resolveSnapshotDates(string $start, string $end): ?array
    {
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
                return null;
            }

            $loanEndDate   = $latest->first();
            $loanStartDate = $latest->last();
        }

        return [
            Carbon::parse((string) $loanStartDate)->toDateString(),
            Carbon::parse((string) $loanEndDate)->toDateString(),
        ];
    }

    /**
     * One row per (snapshot date, related_account) — the latest id for that pair, scoped
     * to the given RM codes and the performing/non-Corporate/non-staff filters. Join this
     * to loan_listings on id to get deduped rows for the given snapshot date(s).
     */
    private function dedupSubquery(array $dates, array $rmCodes)
    {
        return DB::table('loan_listings')
            ->whereIn(DB::raw('DATE(as_at_date)'), $dates)
            ->whereRaw(self::NON_CORPORATE_FILTER)
            ->whereRaw(self::PERFORMING_FILTER)
            ->whereRaw(self::NON_STAFF_FILTER)
            ->whereIn('rm_officer', $rmCodes)
            ->select(DB::raw('DATE(as_at_date) AS snap_date'), 'related_account', DB::raw('MAX(id) AS max_id'))
            ->groupBy(DB::raw('DATE(as_at_date)'), 'related_account');
    }
}
