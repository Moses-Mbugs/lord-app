<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Exports\Finance\RmLoanMoversWorkbookExport;
use App\Mail\RmLoanMoversReportMail;
use App\Services\Reports\RmLoanMoversService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class EmailRmLoanMoversCommand extends Command
{
    protected $signature = 'reports:email-rm-loan-movers
        {start : Start date YYYY-MM-DD (requested start)}
        {end : End date YYYY-MM-DD}
        {--to= : Override TO recipients (comma/semicolon/space separated)}
        {--cc= : Override CC recipients (comma/semicolon/space separated)}
        {--rms= : Override RM sales codes (comma/semicolon/space separated); defaults to reports.balances.rm_portfolio}
        {--drilldown=10 : Top loan account gainers/losers to show across the RM list}
    ';

    protected $description = 'Email RM Loan Movers report (loan portfolio movement) for a fixed portfolio of RM sales codes. Performing book only; Corporate segment and Staff loans (linecode) excluded.';

    public function handle(RmLoanMoversService $service): int
    {
        $start          = Carbon::parse((string) $this->argument('start'))->toDateString();
        $end            = Carbon::parse((string) $this->argument('end'))->toDateString();
        $drilldownLimit = max(1, (int) $this->option('drilldown'));

        $rmCodes = $this->resolveRmCodes();

        if (empty($rmCodes)) {
            $this->error('No RM codes resolved. Set reports.balances.rm_portfolio or pass --rms=');
            return self::FAILURE;
        }

        $toOpt = (string) ($this->option('to') ?? '');
        $to = $toOpt !== ''
            ? $this->parseEmails($toOpt)
            : $this->parseEmails(config('reports.balances.rm_loan_movers_to', []));

        if (empty($to)) {
            $this->error('No TO recipients configured. Set reports.balances.rm_loan_movers_to or pass --to=');
            return self::FAILURE;
        }

        $ccOpt = (string) ($this->option('cc') ?? '');
        $cc = $ccOpt !== ''
            ? $this->parseEmails($ccOpt)
            : $this->parseEmails(config('reports.balances.rm_loan_movers_cc', []));

        $loanBook = $service->loanBookPerRm($start, $end, $rmCodes);
        $snapshot = $service->loanAccountSnapshotPerRm($rmCodes);
        $portfolio = EmailRmMoversCommand::portfolio();

        $rmRows = collect($rmCodes)
            ->map(function ($code) use ($portfolio, $loanBook, $snapshot) {
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
            })
            ->sortBy('rm_name')
            ->values();

        $totals = (object) [
            'account_count'  => (int) $rmRows->sum('account_count'),
            'customer_count' => (int) $rmRows->sum('customer_count'),
            'loan_open'      => (float) $rmRows->sum('loan_open'),
            'loan_close'     => (float) $rmRows->sum('loan_close'),
            'loan_movement'  => (float) $rmRows->sum('loan_movement'),
        ];

        $groupedDrilldown = $service->accountMoversGroupedByRmCodes($start, $end, $rmCodes, $drilldownLimit);

        // Flatten the per-RM grouped drilldown into one merged top-N for the email body
        // (the Excel attachment keeps the full per-RM breakdown).
        $flattened = collect($groupedDrilldown)->flatMap(function ($g, $code) {
            return collect($g['gainers'] ?? [])->merge($g['losers'] ?? [])
                ->map(fn ($r) => array_merge($r, ['rm_code' => $code]));
        });

        $topGainers = $flattened->filter(fn ($r) => $r['movement'] > 0)
            ->sortByDesc(fn ($r) => $r['movement'])->take($drilldownLimit)->values();
        $topLosers = $flattened->filter(fn ($r) => $r['movement'] < 0)
            ->sortBy(fn ($r) => $r['movement'])->take($drilldownLimit)->values();

        $mailable = new RmLoanMoversReportMail($start, $end, $rmRows, $totals, $topGainers, $topLosers);

        $excelName = "RM_Loan_Movers_{$start}_{$end}.xlsx";
        $excelBinary = Excel::raw(
            new RmLoanMoversWorkbookExport($start, $end, $rmRows, $totals, $rmCodes, $portfolio, $groupedDrilldown),
            ExcelWriter::XLSX
        );
        $mailable->attachData(
            $excelBinary,
            $excelName,
            ['mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );

        Mail::to($to)->cc($cc)->send($mailable);

        $this->info('RM loan movers email sent (with Excel attachment).');
        $this->line('TO: ' . implode(', ', $to));
        $this->line('CC: ' . (empty($cc) ? '(none)' : implode(', ', $cc)));
        $this->line("Period: {$start} → {$end} | RMs: " . count($rmCodes));

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
