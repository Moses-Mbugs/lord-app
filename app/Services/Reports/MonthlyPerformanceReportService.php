<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Finance\MonthlyReportSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Monthly reports, each anchored on dates that actually exist in the data — the last
 * balance_date / as_at_date posted in the month — never on calendar month-ends,
 * since month-ends often fall on weekends with no Flexcube file.
 *
 *  - build():         Deposits (Bank/LCY/FCY by segment, MoM + YTD) and Loans (performing
 *                     book by segment, MoM) — the "Loans & Deposits" email.
 *  - buildBranches(): per-branch Deposits (MoM + YTD), Loans (MoM) and NTB — the
 *                     separate monthly branch email.
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
        [$monthDate, $monthEnd] = $this->resolveMonth($month);

        return $this->header($monthDate) + [
            'deposits' => $this->buildDeposits($monthEnd, $limit),
            'loans'    => $this->buildLoans($monthDate, $limit),
        ];
    }

    /** @param string $month YYYY-MM */
    public function buildBranches(string $month): array
    {
        [$monthDate, $monthEnd] = $this->resolveMonth($month);

        $depPeriods = [
            'month_start' => $this->latestBalanceDateInMonth($monthDate->copy()->subMonthNoOverflow()) ?? $monthEnd,
            'month_end'   => $monthEnd,
            'ytd_start'   => $this->depositYtdStart($monthEnd),
        ];

        $loanPeriods = $this->resolveLoanPeriods($monthDate)['periods'];

        return $this->header($monthDate) + [
            'deposit_periods' => $depPeriods,
            'loan_periods'    => $loanPeriods,
        ] + $this->buildBranchRows($monthDate, $depPeriods, $loanPeriods);
    }

    // -------------------------------------------------------------------------
    // Stored snapshots (monthly_report_snapshots)
    // -------------------------------------------------------------------------

    /**
     * Save a build() / buildBranches() result so the email can be (re)sent without rebuilding.
     *
     * @param string $type MonthlyReportSnapshot::TYPE_*
     */
    public function save(string $type, array $report): void
    {
        [$depositsAsAt, $loanPeriods] = $this->dataDates($type, $report);

        MonthlyReportSnapshot::updateOrCreate(
            ['report_type' => $type, 'month' => $report['month']],
            [
                'deposits_as_at' => $depositsAsAt,
                'loans_start'    => $loanPeriods['month_start'] ?? null,
                'loans_as_at'    => $loanPeriods['month_end'] ?? null,
                'payload'        => $report,
            ]
        );
    }

    /**
     * The stored report for $month, or null when there is none or it is stale — i.e. a newer
     * balances or loan file has landed for that month (or the month before, for loans) since
     * it was built, so a rebuild would give different numbers.
     *
     * @param string $type MonthlyReportSnapshot::TYPE_*
     */
    public function loadFresh(string $type, string $month): ?array
    {
        $snapshot = MonthlyReportSnapshot::where('report_type', $type)->where('month', $month)->first();
        if ($snapshot === null) return null;

        [$monthDate, $monthEnd] = $this->resolveMonth($month);
        $loanPeriods = $this->resolveLoanPeriods($monthDate)['periods'];

        $isFresh = $snapshot->deposits_as_at->toDateString() === $monthEnd
            && $snapshot->loans_start?->toDateString() === ($loanPeriods['month_start'] ?? null)
            && $snapshot->loans_as_at?->toDateString() === ($loanPeriods['month_end'] ?? null);

        if (!$isFresh) return null;

        return $this->hydrate($type, $snapshot->payload) + ['built_at' => $snapshot->updated_at?->toDateTimeString()];
    }

    /** @return array{0: string, 1: ?array} [deposits month-end date, loan periods or null] */
    private function dataDates(string $type, array $report): array
    {
        return $type === MonthlyReportSnapshot::TYPE_BRANCHES
            ? [$report['deposit_periods']['month_end'], $report['loan_periods']]
            : [$report['deposits']['periods']['month_end'], $report['loans']['periods']];
    }

    /**
     * JSON turns the top-movers Collections of row objects into plain arrays; the email
     * views and Excel exports read them as objects ($r->cif), so restore that shape.
     * is_month_closed is relative to today, so it's recomputed rather than trusted.
     */
    private function hydrate(string $type, array $report): array
    {
        $rows = fn(array $top) => [
            'gainers' => collect($top['gainers'] ?? [])->map(fn($r) => (object) $r)->values(),
            'losers'  => collect($top['losers'] ?? [])->map(fn($r) => (object) $r)->values(),
        ];

        if ($type === MonthlyReportSnapshot::TYPE_BRANCHES) {
            foreach ($report['top'] ?? [] as $key => $top) {
                $report['top'][$key] = $rows($top);
            }
        } else {
            $report['deposits']['top'] = $rows($report['deposits']['top'] ?? []);
            $report['loans']['top']    = $rows($report['loans']['top'] ?? []);
        }

        return array_merge($report, $this->header(Carbon::createFromFormat('!Y-m', $report['month'])));
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
     * their own. periods = null when the month (or the month before it) has no loan
     * snapshot — the email then says so rather than showing a fake movement.
     *
     * @return array{periods: ?array, missing: ?string}
     */
    private function resolveLoanPeriods(Carbon $monthDate): array
    {
        $prev       = $monthDate->copy()->subMonthNoOverflow();
        $monthEnd   = $this->loans->latestAvailableInMonth($monthDate->year, $monthDate->month);
        $monthStart = $this->loans->latestAvailableInMonth($prev->year, $prev->month);

        if ($monthEnd === null || $monthStart === null) {
            return ['periods' => null, 'missing' => $monthEnd === null ? $monthDate->format('F Y') : $prev->format('F Y')];
        }

        return [
            'periods' => [
                'month_start' => $monthStart,
                'month_end'   => $monthEnd,
                'ytd_start'   => $this->loans->findYtdStart($monthEnd),
            ],
            'missing' => null,
        ];
    }

    private function buildLoans(Carbon $monthDate, int $limit): array
    {
        $resolved = $this->resolveLoanPeriods($monthDate);
        $periods  = $resolved['periods'];

        if ($periods === null) {
            return $resolved + ['segments' => [], 'top' => ['gainers' => collect(), 'losers' => collect()]];
        }

        return $resolved + [
            'segments' => $this->loans->buildMonthly($periods['month_start'], $periods['month_end'], $periods['ytd_start']),
            'top'      => $this->loans->topMovers($periods['month_start'], $periods['month_end'], $limit),
        ];
    }

    // -------------------------------------------------------------------------
    // Branches
    // -------------------------------------------------------------------------

    /** @return array{periods: array, ntb_periods: array, rows: array, top: array} */
    private function buildBranchRows(Carbon $monthDate, array $depPeriods, ?array $loanPeriods): array
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

        // Loans are month-on-month only (not enough loan history for a meaningful YTD).
        $loans = $loanPeriods
            ? $this->branchMetrics->loanTotals($loanPeriods['month_start'], $loanPeriods['month_end'], false)
            : [];

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

            $ntb = $this->branchMetrics->ntbCounts($ntbPeriods[$key]['start'], $ntbPeriods[$key]['end']);

            foreach ($summary as $r) {
                $code = strtoupper(trim((string) ($r->group_key ?? '')));
                if ($code === '') continue;

                $rows[$code] ??= [
                    'code' => $code, 'name' => (string) ($r->group_name ?? $code),
                    'dep_month' => 0.0, 'dep_ytd' => 0.0, 'dep_balance' => 0.0,
                    'loan_month' => 0.0, 'loan_balance' => 0.0,
                    'ntb_month' => 0, 'ntb_ytd' => 0,
                ];

                $rows[$code]["dep_{$key}"] = (float) ($r->movement ?? 0);
                $rows[$code]["ntb_{$key}"] = (int) ($ntb[$code] ?? 0);

                if ($key === 'month') {
                    $loan = $loans[$code] ?? ['open' => 0.0, 'close' => 0.0];
                    $rows[$code]['dep_balance']  = (float) ($r->end_balance ?? 0);
                    $rows[$code]['loan_month']   = $loan['close'] - $loan['open'];
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

    /** @return array{0: Carbon, 1: string} [first day of the month, last balance_date in it] */
    private function resolveMonth(string $month): array
    {
        $monthDate = Carbon::createFromFormat('!Y-m', $month);
        if ($monthDate === false) {
            throw new RuntimeException("Invalid month '{$month}', expected YYYY-MM.");
        }

        $monthEnd = $this->latestBalanceDateInMonth($monthDate);
        if ($monthEnd === null) {
            throw new RuntimeException("No customer_balances data for {$monthDate->format('F Y')}.");
        }

        return [$monthDate, $monthEnd];
    }

    private function header(Carbon $monthDate): array
    {
        return [
            'month'           => $monthDate->format('Y-m'),
            'label'           => $monthDate->format('F Y'),
            'is_month_closed' => $monthDate->copy()->endOfMonth()->lt(now()->timezone('Africa/Nairobi')->startOfDay()),
        ];
    }

    private function latestBalanceDateInMonth(Carbon $monthDate): ?string
    {
        $d = DB::table('customer_balances')
            ->whereYear('balance_date', $monthDate->year)
            ->whereMonth('balance_date', $monthDate->month)
            ->max('balance_date');

        return $d ? Carbon::parse((string) $d)->toDateString() : null;
    }

    /**
     * Last balance_date of the previous year, else the earliest one this year —
     * same rule as WeeklySegmentReportService::findYtdStart().
     */
    private function depositYtdStart(string $monthEnd): string
    {
        $yearStart = Carbon::parse($monthEnd)->startOfYear()->toDateString();

        $d = DB::table('customer_balances')->where('balance_date', '<', $yearStart)->max('balance_date')
            ?? DB::table('customer_balances')
                ->where('balance_date', '>=', $yearStart)
                ->where('balance_date', '<', $monthEnd)
                ->min('balance_date');

        return $d ? Carbon::parse((string) $d)->toDateString() : $monthEnd;
    }
}
