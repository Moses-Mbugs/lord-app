<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\TopMover;
use App\Services\Reports\LoanMovementService;
use App\Services\Reports\TopMoversService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class TopMoversController extends Controller
{
    private const LIMIT = 10;

    private const PERIODS = ['daily', 'weekly', 'monthly'];

    protected $topMoversService;

    protected $loanMovementService;

    public function __construct(TopMoversService $topMoversService, LoanMovementService $loanMovementService)
    {
        $this->topMoversService = $topMoversService;
        $this->loanMovementService = $loanMovementService;
    }

    public function index(Request $request)
    {
        $branches = DB::table('customer_balances')
            ->select('branch_code')
            ->distinct()
            ->whereNotNull('branch_code')
            ->where('branch_code', '<>', '')
            ->orderBy('branch_code')
            ->pluck('branch_code')
            ->filter()
            ->values();

        $lastMovementDate = DB::table('customer_balances')->max('balance_date');

        $lastMovementDate = $lastMovementDate
            ? Carbon::parse($lastMovementDate)->toDateString()
            : null;

        $deposits = $this->buildDepositsDashboard();

        return view('finance.top-movers.index', compact(
            'branches',
            'lastMovementDate',
            'deposits'
        ));
    }

    /**
     * Deposits snapshot for the dashboard — same window, grouping and segment
     * overview as the "Daily Deposits Movement Report" email, so the page
     * always matches what finance already received in their inbox.
     */
    private function buildDepositsDashboard(): ?array
    {
        $window = $this->topMoversService->latestSnapshotWindow();

        if (!$window) {
            return null;
        }

        [$start, $end] = [$window['start'], $window['end']];

        $grouped  = $this->topMoversService->fetchGroupedMovers($start, $end, self::LIMIT, 10);
        $segments = $this->topMoversService->fetchSegmentOverview($start, $end);

        $cifGain = $grouped['CIF_ONLY']['GAIN'];
        $cifLoss = $grouped['CIF_ONLY']['LOSS'];
        $allCif  = $cifGain->concat($cifLoss);

        $kpis = [
            'total_start'    => (float) $allCif->sum('start_balance'),
            'total_end'      => (float) $allCif->sum('end_balance'),
            'total_movement' => (float) $allCif->sum('movement'),
            'gainers_count'  => $cifGain->count(),
            'losers_count'   => $cifLoss->count(),
        ];

        return [
            'start'    => $start,
            'end'      => $end,
            'grouped'  => $grouped,
            'segments' => $segments,
            'kpis'     => $kpis,
        ];
    }

    public function data(Request $request)
    {
        $direction = strtoupper((string) $request->input('direction', 'GAIN'));

        if (!in_array($direction, ['GAIN', 'LOSS'], true)) {
            $direction = 'GAIN';
        }

        $currencyType = strtoupper((string) $request->input('currency_type', 'LCY'));

        if (!in_array($currencyType, ['LCY', 'FCY'], true)) {
            $currencyType = 'LCY';
        }

        $period = strtolower((string) $request->input('period', 'daily'));

        if (!in_array($period, self::PERIODS, true)) {
            $period = 'daily';
        }

        $dateTo = $this->resolveEndDate();

        if (!$dateTo) {
            return DataTables::of(collect())->make(true);
        }

        $dateFrom = $this->resolvePeriodStart($period, $dateTo);

        if (!$dateFrom) {
            return DataTables::of(collect())->make(true);
        }

        $branch = $request->filled('branch_code')
            ? trim((string) $request->input('branch_code'))
            : null;

        $shouldBuildMissing = $request->boolean('build_missing', false);

        if ($shouldBuildMissing) {
            try {
                $this->ensureTopMoversCache($dateFrom, $dateTo, $currencyType);
            } catch (\Throwable $e) {
                Log::error('TopMoversController: failed to build top movers cache', [
                    'date_from'     => $dateFrom,
                    'date_to'       => $dateTo,
                    'currency_type' => $currencyType,
                    'exception'     => $e->getMessage(),
                ]);

                return DataTables::of(collect())->make(true);
            }
        }

        $query = TopMover::query()
            ->where('scope', TopMoversService::SCOPE_CIF_CURRENCY)
            ->whereDate('start_date', $dateFrom)
            ->whereDate('end_date', $dateTo)
            ->where('currency_type', $currencyType)
            ->where('direction', $direction)
            ->when($branch, function ($q) use ($branch) {
                $q->where('branch_code', $branch);
            })
            ->select([
                'id',
                'cif',
                'customer_name',
                'currency',
                'branch_code',
                'cust_ac_no',
                'start_date',
                'end_date',
                'start_balance',
                'end_balance',
                'movement',
                'direction',
            ]);

        if ($direction === 'GAIN') {
            $query->orderByDesc('movement');
        } else {
            $query->orderBy('movement');
        }

        if ($request->boolean('export')) {
            return $this->exportCsv(clone $query, $direction, $dateFrom, $dateTo);
        }

        return DataTables::of($query)
            ->addColumn('start_balance_fmt', function ($row) {
                return number_format((float) $row->start_balance, 2);
            })
            ->addColumn('end_balance_fmt', function ($row) {
                return number_format((float) $row->end_balance, 2);
            })
            ->addColumn('movement_fmt', function ($row) {
                return number_format(abs((float) $row->movement), 2);
            })
            ->addColumn('pct_change', function ($row) {
                return $this->percentageChange($row->start_balance, $row->end_balance);
            })
            ->with([
                'period_start' => $dateFrom,
                'period_end'   => $dateTo,
            ])
            ->make(true);
    }

    /**
     * Latest available balance date in the system (the "as of" date for Daily/Weekly/Monthly).
     */
    private function resolveEndDate(): ?string
    {
        $max = DB::table('customer_balances')->max('balance_date');

        return $max ? Carbon::parse($max)->toDateString() : null;
    }

    /**
     * Resolve the comparison start date for a period, snapping to the nearest
     * available balance_date on/before the target cutoff (weekends/holidays safe).
     */
    private function resolvePeriodStart(string $period, string $endDate): ?string
    {
        if ($period === 'daily') {
            $start = DB::table('customer_balances')
                ->where('balance_date', '<', $endDate)
                ->max('balance_date');

            return $start ? Carbon::parse($start)->toDateString() : null;
        }

        $cutoff = $period === 'weekly'
            ? Carbon::parse($endDate)->subDays(7)->toDateString()
            : Carbon::parse($endDate)->subMonth()->toDateString();

        $start = DB::table('customer_balances')
            ->where('balance_date', '<=', $cutoff)
            ->max('balance_date');

        if (!$start) {
            // Not enough history for a full week/month yet — fall back to the earliest
            // available balance_date before the end date so the page still shows data.
            $start = DB::table('customer_balances')
                ->where('balance_date', '<', $endDate)
                ->min('balance_date');
        }

        return $start ? Carbon::parse($start)->toDateString() : null;
    }

    /**
     * Loans top movers (gainers/losers), reusing LoanMovementService::topMovers().
     * loan_listings.as_at_date is populated by manual Loan Book uploads (no daily
     * cron), so unlike deposits, ALL periods here snap to the nearest available
     * date and fall back to the earliest prior snapshot if there isn't one.
     */
    public function loansData(Request $request)
    {
        $direction = strtoupper((string) $request->input('direction', 'GAIN'));

        if (!in_array($direction, ['GAIN', 'LOSS'], true)) {
            $direction = 'GAIN';
        }

        $currencyType = strtoupper((string) $request->input('currency_type', 'LCY'));

        if (!in_array($currencyType, ['LCY', 'FCY'], true)) {
            $currencyType = 'LCY';
        }

        $period = strtolower((string) $request->input('period', 'daily'));

        if (!in_array($period, self::PERIODS, true)) {
            $period = 'daily';
        }

        $dateTo = $this->resolveLoanEndDate();

        if (!$dateTo) {
            return DataTables::of(collect())->make(true);
        }

        $dateFrom = $this->resolveLoanPeriodStart($period, $dateTo);

        if (!$dateFrom) {
            return DataTables::of(collect())->make(true);
        }

        try {
            $movers = $this->loanMovementService->topMovers($dateFrom, $dateTo, $currencyType, self::LIMIT);
        } catch (\Throwable $e) {
            Log::error('TopMoversController: failed to build loan top movers', [
                'date_from'     => $dateFrom,
                'date_to'       => $dateTo,
                'currency_type' => $currencyType,
                'exception'     => $e->getMessage(),
            ]);

            return DataTables::of(collect())->make(true);
        }

        $rows = $direction === 'GAIN' ? $movers['gainers'] : $movers['losers'];

        $mapped = collect($rows)->map(function ($row) {
            $row = (object) $row;

            return (object) [
                'cif'               => $row->cif,
                'customer_name'     => $row->name,
                'branch_code'       => $row->branch,
                'business_segment'  => $row->business_segment,
                'start_balance'     => (float) $row->start_balance,
                'end_balance'       => (float) $row->end_balance,
                'movement'          => (float) $row->movement,
                'start_balance_fmt' => number_format((float) $row->start_balance, 2),
                'end_balance_fmt'   => number_format((float) $row->end_balance, 2),
                'movement_fmt'      => number_format(abs((float) $row->movement), 2),
                'pct_change'        => $this->percentageChange($row->start_balance, $row->end_balance),
            ];
        });

        if ($request->boolean('export')) {
            return $this->exportLoansCsv($mapped, $direction, $dateFrom, $dateTo);
        }

        return DataTables::of($mapped)
            ->with([
                'period_start' => $dateFrom,
                'period_end'   => $dateTo,
            ])
            ->make(true);
    }

    /**
     * Latest available loan_listings snapshot date.
     */
    private function resolveLoanEndDate(): ?string
    {
        $max = DB::table('loan_listings')->whereNotNull('as_at_date')->max('as_at_date');

        return $max ? Carbon::parse($max)->toDateString() : null;
    }

    /**
     * Resolve the comparison start date for loans, snapping to the nearest available
     * as_at_date on/before the target cutoff, with a fallback to the earliest prior
     * snapshot — loan uploads are sparse/manual so every period needs this leniency.
     */
    private function resolveLoanPeriodStart(string $period, string $endDate): ?string
    {
        if ($period === 'daily') {
            $start = DB::table('loan_listings')
                ->where('as_at_date', '<', $endDate)
                ->max('as_at_date');

            return $start ? Carbon::parse($start)->toDateString() : null;
        }

        $cutoff = $period === 'weekly'
            ? Carbon::parse($endDate)->subDays(7)->toDateString()
            : Carbon::parse($endDate)->subMonth()->toDateString();

        $start = DB::table('loan_listings')
            ->where('as_at_date', '<=', $cutoff)
            ->max('as_at_date');

        if (!$start) {
            $start = DB::table('loan_listings')
                ->where('as_at_date', '<', $endDate)
                ->min('as_at_date');
        }

        return $start ? Carbon::parse($start)->toDateString() : null;
    }

    private function ensureTopMoversCache(string $dateFrom, string $dateTo, string $currencyType): void
    {
        $exists = TopMover::query()
            ->where('scope', TopMoversService::SCOPE_CIF_CURRENCY)
            ->whereDate('start_date', $dateFrom)
            ->whereDate('end_date', $dateTo)
            ->where('currency_type', $currencyType)
            ->whereIn('direction', ['GAIN', 'LOSS'])
            ->exists();

        if ($exists) {
            return;
        }

        $this->topMoversService->build(
            $dateFrom,
            $dateTo,
            $currencyType,
            self::LIMIT,
            TopMoversService::SCOPE_CIF_CURRENCY,
            false,
            'end'
        );
    }

    private function exportCsv($query, string $direction, string $dateFrom, string $dateTo)
    {
        $fileDirection = strtolower($direction);
        $filename = "top_movers_{$fileDirection}_{$dateFrom}_to_{$dateTo}.csv";

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'CIF',
                'Customer',
                'Branch',
                'Previous Balance',
                'Current Balance',
                'Day Movement',
                'Percentage Change',
                'Start Date',
                'End Date',
            ]);

            $query->get()->each(function ($row) use ($handle) {
                $pct = $this->percentageChange($row->start_balance, $row->end_balance);

                fputcsv($handle, [
                    $row->cif,
                    $row->customer_name,
                    $row->branch_code,
                    number_format((float) $row->start_balance, 2, '.', ''),
                    number_format((float) $row->end_balance, 2, '.', ''),
                    number_format(abs((float) $row->movement), 2, '.', ''),
                    $pct === null ? '' : number_format((float) $pct, 2, '.', '') . '%',
                    $row->start_date,
                    $row->end_date,
                ]);
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function exportLoansCsv($rows, string $direction, string $dateFrom, string $dateTo)
    {
        $fileDirection = strtolower($direction);
        $filename = "top_movers_loans_{$fileDirection}_{$dateFrom}_to_{$dateTo}.csv";

        return response()->streamDownload(function () use ($rows, $dateFrom, $dateTo) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'CIF',
                'Customer',
                'Branch',
                'Segment',
                'Previous Balance',
                'Current Balance',
                'Movement',
                'Percentage Change',
                'Start Date',
                'End Date',
            ]);

            $rows->each(function ($row) use ($handle, $dateFrom, $dateTo) {
                fputcsv($handle, [
                    $row->cif,
                    $row->customer_name,
                    $row->branch_code,
                    $row->business_segment,
                    number_format((float) $row->start_balance, 2, '.', ''),
                    number_format((float) $row->end_balance, 2, '.', ''),
                    number_format(abs((float) $row->movement), 2, '.', ''),
                    $row->pct_change === null ? '' : number_format((float) $row->pct_change, 2, '.', '') . '%',
                    $dateFrom,
                    $dateTo,
                ]);
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function percentageChange($startBalance, $endBalance): ?float
    {
        $start = (float) $startBalance;
        $end   = (float) $endBalance;

        if ($start == 0.0) {
            return null;
        }

        return round((($end - $start) / abs($start)) * 100, 2);
    }

    private function normalizeDate($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
