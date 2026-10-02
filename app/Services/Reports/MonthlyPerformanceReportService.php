<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Month-on-month + YTD bank performance: Deposits (Bank/LCY/FCY by segment), Loans
 * (performing book by segment) and Branches (deposits, loans, NTB).
 *
 * Every period is anchored on dates that actually exist in the data — the last
 * balance_date / as_at_date posted in the month — never on calendar month-ends,
 * since month-ends often fall on weekends with no Flexcube file.
 */
class MonthlyPerformanceReportService
{
    public function __construct(
        private readonly WeeklySegmentReportService  $deposits,
        private readonly WeeklyLoanReportService     $loans,
        private readonly GroupMoversService          $groupMovers,
        private readonly BranchPeriodMetricsService  $branchMetrics,
    ) {}

    /**
     * @param string $month YYYY-MM
     * @param int    $limit Top CIF gainers/losers per product for the Excel attachment
     */
    public function build(string $month, int $limit = 100): array
    {
        $monthDate = Carbon::createFromFormat('!Y-m', $month);
        if ($monthDate === false) {
            throw new RuntimeException("Invalid month '{$month}', expected YYYY-MM.");
        }

        $monthEnd = $this->latestBalanceDateInMonth($monthDate);
        if ($monthEnd === null) {
            throw new RuntimeException("No customer_balances data for {$monthDate->format('F Y')}.");
        }

        $deposits = $this->buildDeposits($monthEnd, $limit);
        $loans    = $this->buildLoans($monthDate, $limit);
        $branches = $this->buildBranches($monthDate, $deposits['periods'], $loans['periods'] ?? null);

        return [
            'month'          => $monthDate->format('Y-m'),
            'label'          => $monthDate->format('F Y'),
            'is_month_closed' => $monthDate->copy()->endOfMonth()->lt(now()->timezone('Africa/Nairobi')->startOfDay()),
            'deposits'       => $deposits,
            'loans'          => $loans,
            'branches'       => $branches,
        ];
    }

    // -------------------------------------------------------------------------
    // Deposits
    // -------------------------------------------------------------------------

    /**
     * Reuses the weekly deposits build anchored on the month's last balance date —
     * its MTD start is the previous month's last balance date, so its MTD movement
     * is exactly the month-on-month movement, and its YTD start rules are shared.
     */
    private function buildDeposits(string $monthEnd, int $limit): array
    {
        $data = $this->deposits->build($monthEnd);

        $periods = [
            'month_start' => $data['periods']['mtd_start'],
            'month_end'   => $monthEnd,
            'ytd_start'   => $data['periods']['ytd_start'],
        ];

        return [
            'periods' => $periods,
            'bank'    => $this->mapDepositSegments($data['bank']['segments'] ?? []),
            'lcy'     => $this->mapDepositSegments($data['lcy']['segments'] ?? []),
            'fcy'     => $this->mapDepositSegments($data['fcy']['segments'] ?? []),
            'top'     => $this->deposits->topMovers($periods['month_start'], $monthEnd, $limit),
        ];
    }

    private function mapDepositSegments(array $segments): array
    {
        $map = fn(array $s) => [
            'name'     => $s['name'] ?? '',
            'month_mv' => (float) ($s['mtd_mv'] ?? 0),
            'ytd_mv'   => (float) ($s['ytd_mv'] ?? 0),
            'balance'  => (float) ($s['total_deposits'] ?? 0),
        ];

        return array_map(fn(array $s) => ['code' => $s['code']] + $map($s) + [
            'sub_segments' => array_map($map, $s['sub_segments'] ?? []),
        ], $segments);
    }

    // -------------------------------------------------------------------------
    // Loans
    // -------------------------------------------------------------------------

    /**
     * Loans are imported separately from balances, so their dates are resolved on
     * their own. Returns periods = null when the month (or the month before it) has
     * no loan snapshot — the email then says so rather than showing a fake movement.
     */
    private function buildLoans(Carbon $monthDate, int $limit): array
    {
        $prev       = $monthDate->copy()->subMonthNoOverflow();
        $monthEnd   = $this->loans->latestAvailableInMonth($monthDate->year, $monthDate->month);
        $monthStart = $this->loans->latestAvailableInMonth($prev->year, $prev->month);

        if ($monthEnd === null || $monthStart === null) {
            return [
                'periods'  => null,
                'missing'  => $monthEnd === null ? $monthDate->format('F Y') : $prev->format('F Y'),
                'segments' => [],
                'top'      => ['gainers' => collect(), 'losers' => collect()],
            ];
        }

        $periods = [
            'month_start' => $monthStart,
            'month_end'   => $monthEnd,
            'ytd_start'   => $this->loans->findYtdStart($monthEnd),
        ];

        return [
            'periods'  => $periods,
            'missing'  => null,
            'segments' => $this->loans->buildMonthly($periods['month_start'], $periods['month_end'], $periods['ytd_start']),
            'top'      => $this->loans->topMovers($periods['month_start'], $periods['month_end'], $limit),
        ];
    }

    // -------------------------------------------------------------------------
    // Branches
    // -------------------------------------------------------------------------

    private function buildBranches(Carbon $monthDate, array $depPeriods, ?array $loanPeriods): array
    {
        $monthEnd = $depPeriods['month_end'];

        // NTB is counted on calendar account-open dates, so it uses calendar boundaries.
        $ntbPeriods = [
            'month' => ['start' => $monthDate->copy()->subDay()->toDateString(), 'end' => $monthDate->copy()->endOfMonth()->toDateString()],
            'ytd'   => ['start' => $monthDate->copy()->startOfYear()->subDay()->toDateString(), 'end' => $monthDate->copy()->endOfMonth()->toDateString()],
        ];

        $periods = [
            'month' => ['start' => $depPeriods['month_start'], 'end' => $monthEnd],
            'ytd'   => ['start' => $depPeriods['ytd_start'],   'end' => $monthEnd],
        ];

        $rows = [];
        $top  = [];

        foreach ($periods as $key => $p) {
            // Always rebuild: buildBranchMovers() replaces any stored rows for the same window.
            $this->groupMovers->buildBranchMovers($p['start'], $p['end'], 10);
            [$summary, $topRows] = $this->branchMetrics->groupMovers($p['start'], $p['end']);

            $top[$key] = [
                'gainers' => $topRows->where('direction', 'GAIN')->sortBy('rank')->values(),
                'losers'  => $topRows->where('direction', 'LOSS')->sortBy('rank')->values(),
            ];

            $loanStart = $loanPeriods[$key === 'month' ? 'month_start' : 'ytd_start'] ?? null;
            $loans     = $loanPeriods && $loanStart !== $loanPeriods['month_end']
                ? $this->branchMetrics->loanTotals($loanStart, $loanPeriods['month_end'], false)
                : [];
            $ntb = $this->branchMetrics->ntbCounts($ntbPeriods[$key]['start'], $ntbPeriods[$key]['end']);

            foreach ($summary as $r) {
                $code = strtoupper(trim((string) ($r->group_key ?? '')));
                if ($code === '') continue;

                $rows[$code] ??= [
                    'code' => $code, 'name' => (string) ($r->group_name ?? $code),
                    'dep_month' => 0.0, 'dep_ytd' => 0.0, 'dep_balance' => 0.0,
                    'loan_month' => 0.0, 'loan_ytd' => 0.0, 'loan_balance' => 0.0,
                    'ntb_month' => 0, 'ntb_ytd' => 0,
                ];

                $loan = $loans[$code] ?? ['open' => 0.0, 'close' => 0.0];

                $rows[$code]["dep_{$key}"]  = (float) ($r->movement ?? 0);
                $rows[$code]["loan_{$key}"] = $loan['close'] - $loan['open'];
                $rows[$code]["ntb_{$key}"]  = (int) ($ntb[$code] ?? 0);

                if ($key === 'month') {
                    $rows[$code]['dep_balance']  = (float) ($r->end_balance ?? 0);
                    $rows[$code]['loan_balance'] = $loan['close'];
                }
            }
        }

        // Regular branches A-Z, then 834 / 950, then ALL
        uksort($rows, function ($a, $b) {
            $special = ['834' => 1, '950' => 2, 'ALL' => 99];
            $as = $special[$a] ?? 0;
            $bs = $special[$b] ?? 0;
            return $as !== $bs ? $as - $bs : strcmp((string) $a, (string) $b);
        });

        return [
            'periods'     => $periods,
            'ntb_periods' => $ntbPeriods,
            'rows'        => array_values($rows),
            'top'         => $top,
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function latestBalanceDateInMonth(Carbon $monthDate): ?string
    {
        $d = DB::table('customer_balances')
            ->whereYear('balance_date', $monthDate->year)
            ->whereMonth('balance_date', $monthDate->month)
            ->max('balance_date');

        return $d ? Carbon::parse((string) $d)->toDateString() : null;
    }
}
