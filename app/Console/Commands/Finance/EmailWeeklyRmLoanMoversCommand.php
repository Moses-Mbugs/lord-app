<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Exports\Finance\WeeklyRmLoanMoversWorkbookExport;
use App\Mail\WeeklyRmLoanMoversReportMail;
use App\Services\Reports\RmLoanMoversService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class EmailWeeklyRmLoanMoversCommand extends Command
{
    protected $signature = 'reports:email-weekly-rm-loan-movers
        {end? : Report end date YYYY-MM-DD (defaults to latest available balance date)}
        {--to= : Override TO recipients (comma/semicolon/space separated)}
        {--cc= : Override CC recipients (comma/semicolon/space separated)}
        {--rms= : Override RM sales codes (comma/semicolon/space separated); defaults to reports.balances.rm_portfolio}
        {--limit=10 : Top N loan account gainers/losers for the Weekly period}
    ';

    protected $description = 'Email Weekly RM Loan Movers report: Loans (WTD/MTD/YTD) per RM, ranked by weekly performance. Performing book only; Corporate segment and Staff loans (linecode) excluded.';

    public function handle(RmLoanMoversService $service): int
    {
        $endArg  = trim((string) ($this->argument('end') ?? ''));
        $weekEnd = $endArg !== '' ? Carbon::parse($endArg)->toDateString() : $this->findLatestBalanceDate();

        if (!$weekEnd) {
            $this->error('Cannot determine end date — no balance data found and no end argument provided.');
            return self::FAILURE;
        }

        $limit   = max(1, (int) $this->option('limit'));
        $rmCodes = $this->resolveRmCodes();

        if (empty($rmCodes)) {
            $this->error('No RM codes resolved. Set reports.balances.rm_portfolio or pass --rms=');
            return self::FAILURE;
        }

        $toOpt = (string) ($this->option('to') ?? '');
        $to = $toOpt !== ''
            ? $this->parseEmails($toOpt)
            : $this->parseEmails(config(
                'reports.balances.weekly_rm_loan_movers_to',
                config('reports.balances.rm_loan_movers_to', [])
            ));

        if (empty($to)) {
            $this->error('No TO recipients configured. Set reports.balances.weekly_rm_loan_movers_to or pass --to=');
            return self::FAILURE;
        }

        $ccOpt = (string) ($this->option('cc') ?? '');
        $cc = $ccOpt !== ''
            ? $this->parseEmails($ccOpt)
            : $this->parseEmails(config(
                'reports.balances.weekly_rm_loan_movers_cc',
                config('reports.balances.rm_loan_movers_cc', [])
            ));

        $weekEndDate = Carbon::parse($weekEnd);
        $periods = [
            'week' => ['start' => $this->resolveWeekStart($weekEnd), 'end' => $weekEnd, 'label' => 'Weekly'],
            'mtd'  => ['start' => $this->resolveMtdStart($weekEnd),  'end' => $weekEnd, 'label' => 'MTD'],
            'ytd'  => ['start' => $this->resolveYtdStart($weekEnd),  'end' => $weekEnd, 'label' => 'YTD'],
        ];

        $this->line("Week ending : {$weekEnd}");
        $this->line("  Weekly    : {$periods['week']['start']} → {$weekEnd}");
        $this->line("  MTD       : {$periods['mtd']['start']} → {$weekEnd}");
        $this->line("  YTD       : {$periods['ytd']['start']} → {$weekEnd}");

        $portfolio = EmailRmMoversCommand::portfolio();
        $snapshot  = $service->loanAccountSnapshotPerRm($rmCodes);

        $data = [];
        foreach ($periods as $key => $period) {
            $loanBook = $service->loanBookPerRm($period['start'], $period['end'], $rmCodes);

            $summary = collect($rmCodes)->map(function ($code) use ($loanBook, $snapshot, $portfolio) {
                $loan = $loanBook[$code] ?? ['open' => 0.0, 'close' => 0.0];
                $snap = $snapshot[$code] ?? ['account_count' => 0, 'customer_count' => 0];

                return (object) [
                    'rm_code'        => $code,
                    'rm_name'        => $portfolio[$code] ?? $code,
                    'account_count'  => (int) $snap['account_count'],
                    'customer_count' => (int) $snap['customer_count'],
                    'loan_open'      => (float) $loan['open'],
                    'loan_close'     => (float) $loan['close'],
                    'loan_movement'  => round((float) $loan['close'] - (float) $loan['open'], 2),
                ];
            })->values();

            $all = (object) [
                'account_count'  => (int) $summary->sum('account_count'),
                'customer_count' => (int) $summary->sum('customer_count'),
                'loan_open'      => (float) $summary->sum('loan_open'),
                'loan_close'     => (float) $summary->sum('loan_close'),
                'loan_movement'  => (float) $summary->sum('loan_movement'),
            ];

            $data[$key] = ['period' => $period, 'summary' => $summary, 'all' => $all];
        }

        $weekPeriod = $periods['week'];
        $groupedDrilldown = $service->accountMoversGroupedByRmCodes($weekPeriod['start'], $weekPeriod['end'], $rmCodes, $limit);

        $flattened = collect($groupedDrilldown)->flatMap(function ($g, $code) {
            return collect($g['gainers'] ?? [])->merge($g['losers'] ?? [])
                ->map(fn ($r) => array_merge($r, ['rm_code' => $code]));
        });

        $data['week']['topGainers'] = $flattened->filter(fn ($r) => $r['movement'] > 0)
            ->sortByDesc(fn ($r) => $r['movement'])->take($limit)->values();
        $data['week']['topLosers'] = $flattened->filter(fn ($r) => $r['movement'] < 0)
            ->sortBy(fn ($r) => $r['movement'])->take($limit)->values();

        $mailable = new WeeklyRmLoanMoversReportMail($weekEnd, $periods, $data);

        $excelName = "Weekly_RM_Loan_Movers_{$weekEnd}.xlsx";
        $excelBinary = Excel::raw(
            new WeeklyRmLoanMoversWorkbookExport($weekEnd, $periods, $data, $rmCodes, $portfolio, $groupedDrilldown),
            ExcelWriter::XLSX
        );
        $mailable->attachData(
            $excelBinary,
            $excelName,
            ['mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );

        Mail::to($to)->cc($cc)->send($mailable);

        $this->info('Weekly RM loan movers email sent (with Excel attachment).');
        $this->line('TO: ' . implode(', ', $to));
        $this->line('CC: ' . (empty($cc) ? '(none)' : implode(', ', $cc)));
        $this->line('RMs: ' . count($rmCodes));

        return self::SUCCESS;
    }

    private function resolveRmCodes(): array
    {
        $opt = (string) ($this->option('rms') ?? '');

        if (trim($opt) === '') {
            return array_keys(EmailRmMoversCommand::portfolio());
        }

        return collect(preg_split('/[,\s;]+/', $opt) ?: [])
            ->map(fn ($c) => strtoupper(trim((string) $c)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** Latest balance_date on or before (weekEnd − 7 days) — same anchor as the other weekly reports. */
    private function resolveWeekStart(string $weekEnd): string
    {
        $target = Carbon::parse($weekEnd)->subDays(7)->toDateString();

        $d = DB::table('customer_balances')
            ->where('balance_date', '<=', $target)
            ->max('balance_date');

        return $d ? Carbon::parse((string) $d)->toDateString() : $target;
    }

    /** Latest balance_date in the previous calendar month. */
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

    /** Latest balance_date of the previous calendar year. */
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
