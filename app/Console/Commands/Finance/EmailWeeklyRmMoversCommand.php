<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Exports\Finance\WeeklyRmMoversWorkbookExport;
use App\Mail\WeeklyRmMoversReportMail;
use App\Services\Reports\RmLoanMoversService;
use App\Services\Reports\RmMoversService;
use App\Services\Reports\RmPortfolioService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class EmailWeeklyRmMoversCommand extends Command
{
    protected $signature = 'reports:email-weekly-rm-movers
        {end? : Report end date YYYY-MM-DD (defaults to latest available balance date)}
        {--to= : Override TO recipients (comma/semicolon/space separated)}
        {--cc= : Override CC recipients (comma/semicolon/space separated)}
        {--rms= : Override RM sales codes (comma/semicolon/space separated); defaults to reports.balances.rm_portfolio}
        {--segment= : Restrict to one Job Unit segment (Premier|Advantage|Direct); defaults to all segments, as sections in one email}
        {--limit=10 : Top N customer gainers/losers per segment for the Weekly period}
        {--auto-build : Build rm_movers for each period if data is not already stored}
    ';

    protected $description = 'Email Weekly RM Movers report: Deposits (WTD/MTD), Loans (WTD/MTD), and NTB (WTD/MTD/YTD) per RM, one email with a section per Job Unit segment (Premier/Advantage/Direct).';

    public function handle(RmMoversService $service, RmLoanMoversService $loanService): int
    {
        $endArg  = trim((string) ($this->argument('end') ?? ''));
        $weekEnd = $endArg !== '' ? Carbon::parse($endArg)->toDateString() : $this->findLatestBalanceDate();

        if (!$weekEnd) {
            $this->error('Cannot determine end date — no balance data found and no end argument provided.');
            return self::FAILURE;
        }

        $limit     = max(1, (int) $this->option('limit'));
        $autoBuild = (bool) $this->option('auto-build');
        $rmCodes   = $this->resolveRmCodes();

        if (empty($rmCodes)) {
            $this->error('No RM codes resolved for the given --rms/--segment filters.');
            return self::FAILURE;
        }

        $toOpt = (string) ($this->option('to') ?? '');
        $to = $toOpt !== ''
            ? $this->parseEmails($toOpt)
            : $this->parseEmails(config(
                'reports.balances.weekly_rm_movers_to',
                config('reports.balances.rm_movers_to', [])
            ));

        if (empty($to)) {
            $this->error('No TO recipients configured. Set reports.balances.weekly_rm_movers_to or pass --to=');
            return self::FAILURE;
        }

        $ccOpt = (string) ($this->option('cc') ?? '');
        $cc = $ccOpt !== ''
            ? $this->parseEmails($ccOpt)
            : $this->parseEmails(config(
                'reports.balances.weekly_rm_movers_cc',
                config('reports.balances.rm_movers_cc', [])
            ));

        // Deposits: WTD/MTD only. Loans: WTD/MTD only. NTB: WTD/MTD/YTD. Same convention as branches.
        $periods = [
            'week' => ['start' => $this->resolveWeekStart($weekEnd), 'end' => $weekEnd, 'label' => 'Weekly'],
            'mtd'  => ['start' => $this->resolveMtdStart($weekEnd),  'end' => $weekEnd, 'label' => 'MTD'],
            'ytd'  => ['start' => $this->resolveYtdStart($weekEnd),  'end' => $weekEnd, 'label' => 'YTD'],
        ];

        $this->line("Week ending : {$weekEnd}");
        $this->line("  Weekly    : {$periods['week']['start']} → {$weekEnd}");
        $this->line("  MTD       : {$periods['mtd']['start']} → {$weekEnd}");
        $this->line("  YTD       : {$periods['ytd']['start']} → {$weekEnd}");

        // Build the full-portfolio summary once per period (covers every requested RM code);
        // each segment below just filters this down rather than re-fetching.
        $fullSummaryByPeriod = [];
        foreach ($periods as $key => $period) {
            $rows = $this->fetchRmRows($period['start'], $period['end'], $rmCodes);

            if ($rows->isEmpty()) {
                if ($autoBuild) {
                    $this->line("  Building {$period['label']} ({$period['start']} → {$period['end']})…");
                    try {
                        $service->build($period['start'], $period['end']);
                        $rows = $this->fetchRmRows($period['start'], $period['end'], $rmCodes);
                    } catch (\Throwable $e) {
                        $this->warn("  Could not auto-build rm_movers for {$period['label']}: " . $e->getMessage());
                    }
                } else {
                    $this->warn("  No data for {$period['label']} ({$period['start']} → {$period['end']}).");
                    $this->warn("  Run: php artisan reports:build-rm-movers {$period['start']} {$period['end']}");
                    $this->warn("  Or re-run this command with --auto-build to build on-the-fly.");
                }
            }

            $loanByRm = $loanService->loanBookPerRm($period['start'], $period['end'], $rmCodes);
            $ntbByRm  = $this->fetchRmNtbCounts($period['start'], $period['end'], $rmCodes);

            $summary = collect($rmCodes)->map(function ($code) use ($rows, $loanByRm, $ntbByRm) {
                $row  = $rows->get($code);
                $loan = $loanByRm[$code] ?? ['open' => 0.0, 'close' => 0.0];

                return (object) [
                    'rm_code'       => $code,
                    'rm_name'       => RmPortfolioService::name($code),
                    'start_balance' => $row ? (float) $row->start_balance : 0.0,
                    'end_balance'   => $row ? (float) $row->end_balance : 0.0,
                    'movement'      => $row ? (float) $row->movement : 0.0,
                    'cif_count'     => $row ? (int) $row->cif_count : 0,
                    'loan_open'     => (float) $loan['open'],
                    'loan_close'    => (float) $loan['close'],
                    'loan_movement' => round((float) $loan['close'] - (float) $loan['open'], 2),
                    'ntb_count'     => (int) ($ntbByRm[$code] ?? 0),
                ];
            })->keyBy('rm_code');

            $fullSummaryByPeriod[$key] = $summary;
        }

        // Grand ('all segments combined') totals per period, for the top-level KPI strip.
        $grandDataByPeriod = [];
        foreach ($fullSummaryByPeriod as $key => $summary) {
            $grandDataByPeriod[$key] = (object) [
                'rm_code'       => 'ALL',
                'rm_name'       => 'Total',
                'start_balance' => (float) $summary->sum('start_balance'),
                'end_balance'   => (float) $summary->sum('end_balance'),
                'movement'      => (float) $summary->sum('movement'),
                'cif_count'     => (int) $summary->sum('cif_count'),
                'loan_open'     => (float) $summary->sum('loan_open'),
                'loan_close'    => (float) $summary->sum('loan_close'),
                'loan_movement' => (float) $summary->sum('loan_movement'),
                'ntb_count'     => (int) $summary->sum('ntb_count'),
            ];
        }

        $segments     = RmPortfolioService::groupBySegment($rmCodes);
        $segmentsData = [];
        $orderedCodes = [];

        foreach ($segments as $segment => $segmentCodes) {
            $segmentsData[$segment] = $this->buildSegmentData(
                $segmentCodes, $periods, $fullSummaryByPeriod, $limit, $service
            );
            $orderedCodes = array_merge($orderedCodes, $segmentCodes);
        }

        $mailable = new WeeklyRmMoversReportMail($weekEnd, $periods, $segmentsData, $grandDataByPeriod, $limit);

        $weekPeriod = $periods['week'];
        $groupedDrilldown = $service->drilldownGroupedByRmCodes($weekPeriod['start'], $weekPeriod['end'], $orderedCodes, $limit);

        $excelName = "Weekly_RM_Movers_{$weekEnd}.xlsx";
        $excelBinary = Excel::raw(
            new WeeklyRmMoversWorkbookExport($weekEnd, $periods, $segmentsData, $grandDataByPeriod, $orderedCodes, RmPortfolioService::names(), $groupedDrilldown),
            ExcelWriter::XLSX
        );
        $mailable->attachData(
            $excelBinary,
            $excelName,
            ['mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );

        Mail::to($to)->cc($cc)->send($mailable);

        $this->info('Weekly RM movers email sent (with Excel attachment).');
        $this->line('TO: ' . implode(', ', $to));
        $this->line('CC: ' . (empty($cc) ? '(none)' : implode(', ', $cc)));
        $this->line('RMs: ' . count($rmCodes) . ' | Segments: ' . implode(', ', array_keys($segments)));

        return self::SUCCESS;
    }

    private function buildSegmentData(
        array $segmentCodes,
        array $periods,
        array $fullSummaryByPeriod,
        int $limit,
        RmMoversService $service
    ): array {
        $data = [];
        foreach ($periods as $key => $period) {
            $summary = $fullSummaryByPeriod[$key]->only($segmentCodes)->values();

            $all = (object) [
                'rm_code'       => 'ALL',
                'rm_name'       => 'Total',
                'start_balance' => (float) $summary->sum('start_balance'),
                'end_balance'   => (float) $summary->sum('end_balance'),
                'movement'      => (float) $summary->sum('movement'),
                'cif_count'     => (int) $summary->sum('cif_count'),
                'loan_open'     => (float) $summary->sum('loan_open'),
                'loan_close'    => (float) $summary->sum('loan_close'),
                'loan_movement' => (float) $summary->sum('loan_movement'),
                'ntb_count'     => (int) $summary->sum('ntb_count'),
            ];

            $data[$key] = ['period' => $period, 'summary' => $summary, 'all' => $all];
        }

        $weekPeriod = $periods['week'];
        $drilldown = $service->drilldownByRmCodes($weekPeriod['start'], $weekPeriod['end'], $segmentCodes, $limit);
        $data['week']['topGainers'] = collect($drilldown['gainers']);
        $data['week']['topLosers']  = collect($drilldown['losers']);

        return $data;
    }

    private function resolveRmCodes(): array
    {
        $opt = (string) ($this->option('rms') ?? '');

        $codes = trim($opt) === ''
            ? RmPortfolioService::codes()
            : collect(preg_split('/[,\s;]+/', $opt) ?: [])
                ->map(fn ($c) => strtoupper(trim((string) $c)))
                ->filter()
                ->unique()
                ->values()
                ->all();

        $segmentOpt = trim((string) ($this->option('segment') ?? ''));
        if ($segmentOpt === '') {
            return $codes;
        }

        return array_values(array_filter(
            $codes,
            fn ($code) => strcasecmp(RmPortfolioService::segment($code), $segmentOpt) === 0
        ));
    }

    /**
     * Latest balance_date on or before (weekEnd − 7 days) — identical convention to
     * EmailWeeklyBranchMoversCommand::resolveWeekStart(), so the RM report's Weekly
     * column lines up with the Branch/Deposits/Loans weekly reports.
     */
    private function resolveWeekStart(string $weekEnd): string
    {
        $target = Carbon::parse($weekEnd)->subDays(7)->toDateString();

        $d = DB::table('customer_balances')
            ->where('balance_date', '<=', $target)
            ->max('balance_date');

        return $d ? Carbon::parse((string) $d)->toDateString() : $target;
    }

    /**
     * Latest balance_date in the previous calendar month — not just calendar
     * startOfMonth()->subDay(), which can land on a date with no snapshot
     * (weekend/holiday) and make RmMoversService::build() throw. Mirrors
     * WeeklySegmentReportService::findMtdStart() / WeeklyLoanReportService's equivalent.
     */
    private function resolveMtdStart(string $weekEnd): string
    {
        $prevMonthEnd = Carbon::parse($weekEnd)->startOfMonth()->subDay();

        $d = DB::table('customer_balances')
            ->whereYear('balance_date', $prevMonthEnd->year)
            ->whereMonth('balance_date', $prevMonthEnd->month)
            ->max('balance_date');

        if ($d) return Carbon::parse((string) $d)->toDateString();

        $d2 = DB::table('customer_balances')
            ->where('balance_date', '<', Carbon::parse($weekEnd)->startOfMonth()->toDateString())
            ->max('balance_date');

        return $d2 ? Carbon::parse((string) $d2)->toDateString() : $weekEnd;
    }

    /**
     * Latest balance_date of the previous calendar year (e.g. 2025-12-30 if 31 Dec
     * has no snapshot). Mirrors WeeklySegmentReportService::findYtdStart() /
     * WeeklyLoanReportService's equivalent — same fix as resolveMtdStart above.
     */
    private function resolveYtdStart(string $weekEnd): string
    {
        $yearStart = Carbon::parse($weekEnd)->startOfYear()->toDateString();

        $d = DB::table('customer_balances')
            ->where('balance_date', '<', $yearStart)
            ->max('balance_date');

        if ($d) return Carbon::parse((string) $d)->toDateString();

        $d2 = DB::table('customer_balances')
            ->where('balance_date', '>=', $yearStart)
            ->where('balance_date', '<', $weekEnd)
            ->min('balance_date');

        return $d2 ? Carbon::parse((string) $d2)->toDateString() : $weekEnd;
    }

    private function findLatestBalanceDate(): ?string
    {
        $date = DB::table('customer_balances')
            ->whereNotNull('balance_date')
            ->max('balance_date');

        return $date ? Carbon::parse((string) $date)->toDateString() : null;
    }

    private function fetchRmRows(string $start, string $end, array $rmCodes): Collection
    {
        return DB::table('rm_movers')
            ->whereDate('start_date', $start)
            ->whereDate('end_date', $end)
            ->whereIn('rm_code', $rmCodes)
            ->get()
            ->keyBy(fn ($r) => strtoupper(trim((string) $r->rm_code)));
    }

    /**
     * NTB — distinct CIFs with a new account opened in (start, end] — per RM, plus 'ALL'
     * (distinct across the whole RM list, not a sum of the per-RM counts, in case the same
     * CIF opened accounts under more than one RM in the period). Staff accounts
     * (account_class = KECATF) are excluded.
     *
     * Mirrors EmailWeeklyBranchMoversCommand::fetchBranchNtbCounts exactly, grouped by
     * acc_ofcr instead of branch_code and scoped to the given RM codes. ac_open_date is
     * stored as free text D-Mon-YY (e.g. "22-Oct-24") — STR_TO_DATE parses it.
     */
    private function fetchRmNtbCounts(string $start, string $end, array $rmCodes): array
    {
        if (empty($rmCodes)) {
            return [];
        }

        $base = DB::table('customer_accounts_imports')
            ->whereNotNull('acc_ofcr')
            ->whereNotNull('f12_cif')
            ->whereNotNull('ac_open_date')
            ->whereRaw("TRIM(acc_ofcr) <> ''")
            ->whereRaw("TRIM(ac_open_date) <> ''")
            ->whereRaw("UPPER(TRIM(COALESCE(account_class, ''))) <> 'KECATF'")
            ->whereIn(DB::raw('UPPER(TRIM(acc_ofcr))'), $rmCodes)
            ->whereRaw("STR_TO_DATE(ac_open_date, '%d-%b-%y') > ?", [$start])
            ->whereRaw("STR_TO_DATE(ac_open_date, '%d-%b-%y') <= ?", [$end]);

        $rows = (clone $base)
            ->selectRaw("UPPER(TRIM(acc_ofcr)) as rm_code, COUNT(DISTINCT f12_cif) as ntb_count")
            ->groupByRaw("UPPER(TRIM(acc_ofcr))")
            ->get();

        $result = [];
        foreach ($rows as $r) {
            $code = strtoupper(trim((string) $r->rm_code));
            if ($code === '') continue;
            $result[$code] = (int) $r->ntb_count;
        }

        $result['ALL'] = (int) ((clone $base)->selectRaw('COUNT(DISTINCT f12_cif) as agg')->value('agg') ?? 0);

        return $result;
    }

    private function parseEmails(array|string|null $input): array
    {
        if (is_array($input)) {
            $emails = $input;
        } else {
            $raw = trim((string) ($input ?? ''));
            if ($raw === '') return [];
            $emails = preg_split('/[,\s;]+/', $raw) ?: [];
        }

        $emails = array_map(fn ($e) => strtolower(trim((string) $e)), $emails);
        $emails = array_values(array_filter($emails, fn ($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)));

        return array_values(array_unique($emails));
    }
}
