<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Exports\Finance\RmMoversWorkbookExport;
use App\Mail\RmMoversReportMail;
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

class EmailRmMoversCommand extends Command
{
    protected $signature = 'reports:email-rm-movers
        {start : Start date YYYY-MM-DD (requested start)}
        {end : End date YYYY-MM-DD}
        {--to= : Override TO recipients (comma/semicolon/space separated)}
        {--cc= : Override CC recipients (comma/semicolon/space separated)}
        {--rms= : Override RM sales codes (comma/semicolon/space separated); defaults to the tracked portfolio list}
        {--segment= : Restrict to one Job Unit segment (Premier|Advantage|Direct); defaults to all segments present, one email each}
        {--drilldown=10 : Top customer gainers/losers to show across the RM list}
    ';

    protected $description = 'Email RM Movers report (deposit movement), one email per Job Unit segment (Premier/Advantage/Direct), reading/building from rm_movers.';

    public function handle(RmMoversService $service, RmLoanMoversService $loanService): int
    {
        $requestedStart = Carbon::parse((string) $this->argument('start'))->toDateString();
        $end            = Carbon::parse((string) $this->argument('end'))->toDateString();
        $drilldownLimit = max(1, (int) $this->option('drilldown'));

        $rmCodes = $this->resolveRmCodes();

        if (empty($rmCodes)) {
            $this->error('No RM codes resolved for the given --rms/--segment filters.');
            return self::FAILURE;
        }

        $toOpt = (string) ($this->option('to') ?? '');
        $to = $toOpt !== ''
            ? $this->parseEmails($toOpt)
            : $this->parseEmails(config('reports.balances.rm_movers_to', []));

        if (empty($to)) {
            $this->error('No TO recipients configured. Set reports.balances.rm_movers_to or pass --to=');
            return self::FAILURE;
        }

        $ccOpt = (string) ($this->option('cc') ?? '');
        $cc = $ccOpt !== ''
            ? $this->parseEmails($ccOpt)
            : $this->parseEmails(config('reports.balances.rm_movers_cc', []));

        // 1) Try requested start
        $effectiveStart = $requestedStart;
        $rows = $this->fetchRows($effectiveStart, $end, $rmCodes);

        // 2) Fallback: latest start_date already built for this end_date
        if ($rows->isEmpty()) {
            $fallbackStart = DB::table('rm_movers')
                ->whereDate('end_date', $end)
                ->max('start_date');

            if ($fallbackStart) {
                $effectiveStart = Carbon::parse((string) $fallbackStart)->toDateString();
                $rows = $this->fetchRows($effectiveStart, $end, $rmCodes);
            }
        }

        // 3) Fallback: build it on the fly for the requested period
        if ($rows->isEmpty()) {
            try {
                $service->build($requestedStart, $end);
                $effectiveStart = $requestedStart;
                $rows = $this->fetchRows($effectiveStart, $end, $rmCodes);
            } catch (\Throwable $e) {
                $this->warn("Could not auto-build rm_movers for {$requestedStart} → {$end}: " . $e->getMessage());
            }
        }

        if ($rows->isEmpty()) {
            $this->warn("No rm_movers rows found for requested {$requestedStart} → {$end} (and no fallback found).");
            $this->warn("Run: php artisan reports:build-rm-movers {$requestedStart} {$end}");
            return self::FAILURE;
        }

        if ($effectiveStart !== $requestedStart) {
            $this->line("⚠ Fallback applied: using {$effectiveStart} → {$end} (requested was {$requestedStart} → {$end})");
        }

        $loanData    = $loanService->loanBookPerRm($effectiveStart, $end, $rmCodes);
        $accountData = $this->fetchAccountSnapshot($rmCodes);

        $segments = RmPortfolioService::groupBySegment($rmCodes);
        $sentAny  = false;

        foreach ($segments as $segment => $segmentCodes) {
            $sent = $this->sendSegment(
                $segment, $segmentCodes, $effectiveStart, $end, $drilldownLimit,
                $rows, $loanData, $accountData, $service, $to, $cc
            );
            $sentAny = $sentAny || $sent;
        }

        return $sentAny ? self::SUCCESS : self::FAILURE;
    }

    private function sendSegment(
        string $segment,
        array $segmentCodes,
        string $effectiveStart,
        string $end,
        int $drilldownLimit,
        Collection $rows,
        array $loanData,
        array $accountData,
        RmMoversService $service,
        array $to,
        array $cc
    ): bool {
        $rmRows = collect($segmentCodes)
            ->map(function ($code) use ($rows, $loanData, $accountData) {
                $row     = $rows->get($code);
                $loan    = $loanData[$code] ?? ['open' => 0.0, 'close' => 0.0];
                $account = $accountData[$code] ?? null;

                return (object) [
                    'rm_code'        => $code,
                    'rm_name'        => RmPortfolioService::name($code),
                    'start_balance'  => $row ? (float) $row->start_balance : 0.0,
                    'end_balance'    => $row ? (float) $row->end_balance : 0.0,
                    'movement'       => $row ? (float) $row->movement : 0.0,
                    'cif_count'      => $account ? (int) $account->total_customers : ($row ? (int) $row->cif_count : 0),
                    'total_accounts' => $account ? (int) $account->total_accounts : 0,
                    'loan_open'      => (float) $loan['open'],
                    'loan_close'     => (float) $loan['close'],
                    'loan_movement'  => round((float) $loan['close'] - (float) $loan['open'], 2),
                ];
            })
            ->sortBy('rm_name')
            ->values();

        $totals = (object) [
            'start_balance'  => (float) $rmRows->sum('start_balance'),
            'end_balance'    => (float) $rmRows->sum('end_balance'),
            'movement'       => (float) $rmRows->sum('movement'),
            'cif_count'      => (int) $rmRows->sum('cif_count'),
            'total_accounts' => (int) $rmRows->sum('total_accounts'),
            'loan_open'      => (float) $rmRows->sum('loan_open'),
            'loan_close'     => (float) $rmRows->sum('loan_close'),
            'loan_movement'  => (float) $rmRows->sum('loan_movement'),
        ];

        $drilldown        = $service->drilldownByRmCodes($effectiveStart, $end, $segmentCodes, $drilldownLimit);
        $groupedDrilldown = $service->drilldownGroupedByRmCodes($effectiveStart, $end, $segmentCodes, $drilldownLimit);

        $mailable = new RmMoversReportMail(
            $effectiveStart,
            $end,
            $rmRows,
            $totals,
            collect($drilldown['gainers']),
            collect($drilldown['losers']),
            $segment
        );

        $excelName = "RM_Movers_{$segment}_{$effectiveStart}_{$end}.xlsx";
        $excelBinary = Excel::raw(
            new RmMoversWorkbookExport($effectiveStart, $end, $rmRows, $totals, $segmentCodes, RmPortfolioService::names(), $groupedDrilldown),
            ExcelWriter::XLSX
        );
        $mailable->attachData(
            $excelBinary,
            $excelName,
            ['mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );

        Mail::to($to)->cc($cc)->send($mailable);

        $this->info("[{$segment}] RM movers email sent (with Excel attachment).");
        $this->line("[{$segment}] TO: " . implode(', ', $to));
        $this->line("[{$segment}] CC: " . (empty($cc) ? '(none)' : implode(', ', $cc)));
        $this->line("[{$segment}] Period: {$effectiveStart} → {$end} | RMs: " . count($segmentCodes));

        return true;
    }

    private function fetchRows(string $start, string $end, array $rmCodes): Collection
    {
        return DB::table('rm_movers')
            ->whereDate('start_date', $start)
            ->whereDate('end_date', $end)
            ->whereIn('rm_code', $rmCodes)
            ->get()
            ->keyBy(fn ($r) => strtoupper(trim((string) $r->rm_code)));
    }

    /**
     * Current portfolio snapshot per RM: distinct customers (CIF) and total accounts managed,
     * from customer_accounts_imports.acc_ofcr. Not date-scoped — this table reflects the
     * latest import, same convention as BuildRmWorkloadCommand's customer count.
     * Staff accounts (account_class = KECATF) are excluded.
     *
     * @return array<string, object{total_customers:int, total_accounts:int}>
     */
    private function fetchAccountSnapshot(array $rmCodes): array
    {
        if (empty($rmCodes)) {
            return [];
        }

        return DB::table('customer_accounts_imports')
            ->selectRaw("
                UPPER(TRIM(acc_ofcr)) AS rm_code,
                COUNT(DISTINCT TRIM(f12_cif)) AS total_customers,
                COUNT(*) AS total_accounts
            ")
            ->whereNotNull('acc_ofcr')
            ->whereRaw("TRIM(acc_ofcr) <> ''")
            ->whereRaw("UPPER(TRIM(COALESCE(account_class, ''))) <> 'KECATF'")
            ->whereRaw(
                'UPPER(TRIM(acc_ofcr)) IN (' . implode(',', array_fill(0, count($rmCodes), '?')) . ')',
                $rmCodes
            )
            ->groupBy(DB::raw('UPPER(TRIM(acc_ofcr))'))
            ->get()
            ->keyBy(fn ($r) => strtoupper(trim((string) $r->rm_code)))
            ->all();
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
