<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * DSA account opening: accounts in customer_accounts_imports whose introducer starts with
 * CN (the DSA code, e.g. CN314), bucketed by ac_open_date into WTD, MTD, month-on-month
 * (current MTD + N prior full months) and week-on-week (Mon–Sun weeks, current week = WTD).
 *
 * Staff accounts (account_class KECATF) are excluded. customer_accounts_imports is the latest
 * snapshot, so accounts closed since they were opened won't appear.
 */
final class DsaAccountOpeningService
{
    public const INTRODUCER_PREFIX = 'CN';

    /**
     * @return array{
     *     asOf: string, wtdStart: string, mtdStart: string,
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

        $windowStart = min(reset($months)['start'], reset($weekCols)['start']);

        $accounts = $this->fetchAccounts($windowStart, $asOfDate->toDateString());

        $wtd = $wtdStart->toDateString();
        $mtd = $mtdStart->toDateString();

        $dsaRows = $accounts
            ->groupBy('dsa_code')
            ->map(fn (Collection $rows, string $code) => $this->summarise($code, $rows, $wtd, $mtd, $months, $weekCols))
            ->sortBy([['mtd_accounts', 'desc'], ['window_accounts', 'desc'], ['dsa_code', 'asc']])
            ->values();

        return [
            'asOf'     => $asOfDate->toDateString(),
            'wtdStart' => $wtd,
            'mtdStart' => $mtd,
            'months'   => $months,
            'weeks'    => $weekCols,
            'dsaRows'  => $dsaRows,
            'totals'   => $this->summarise('TOTAL', $accounts, $wtd, $mtd, $months, $weekCols),
            'accounts' => $accounts,
        ];
    }

    /** One row per DSA-introduced account opened in [$start, $end], oldest first. */
    private function fetchAccounts(string $start, string $end): Collection
    {
        return DB::table('customer_accounts_imports')
            ->selectRaw("
                UPPER(TRIM(introducer)) AS dsa_code,
                TRIM(f12_cif)           AS cif,
                TRIM(cust_ac_no)        AS account_no,
                TRIM(ac_desc)           AS customer_name,
                TRIM(branch_code)       AS branch_code,
                ac_open_date
            ")
            ->whereRaw('UPPER(TRIM(introducer)) LIKE ?', [self::INTRODUCER_PREFIX . '%'])
            ->whereRaw(StaffExclusion::accountSql())
            ->whereBetween('ac_open_date', [$start, $end])
            ->orderBy('ac_open_date')
            ->orderBy('introducer')
            ->get()
            ->map(function ($r) {
                $open = Carbon::parse((string) $r->ac_open_date);

                $r->open_date = $open->toDateString();
                $r->week_key  = $open->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
                $r->month_key = $open->format('Y-m');

                return $r;
            });
    }

    private function summarise(string $code, Collection $rows, string $wtdStart, string $mtdStart, array $months, array $weeks): object
    {
        $wtd     = $rows->filter(fn ($r) => $r->open_date >= $wtdStart);
        $mtd     = $rows->filter(fn ($r) => $r->open_date >= $mtdStart);
        $byMonth = $rows->countBy('month_key');
        $byWeek  = $rows->countBy('week_key');

        return (object) [
            'dsa_code'        => $code,
            'wtd_accounts'    => $wtd->count(),
            'wtd_customers'   => $wtd->pluck('cif')->filter()->unique()->count(),
            'mtd_accounts'    => $mtd->count(),
            'mtd_customers'   => $mtd->pluck('cif')->filter()->unique()->count(),
            'months'          => array_map(fn ($k) => (int) ($byMonth[$k] ?? 0), array_combine(array_keys($months), array_keys($months))),
            'weeks'           => array_map(fn ($k) => (int) ($byWeek[$k] ?? 0), array_combine(array_keys($weeks), array_keys($weeks))),
            'window_accounts' => $rows->count(),
        ];
    }
}
