<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Exports\Finance\DsaAccountOpeningWorkbookExport;
use App\Mail\DsaAccountOpeningReportMail;
use App\Services\Reports\DsaAccountOpeningService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class EmailDsaAccountOpeningCommand extends Command
{
    protected $signature = 'reports:email-dsa-accounts
        {date? : As-at date YYYY-MM-DD (defaults to today)}
        {--to= : Override TO recipients (comma/semicolon/space separated)}
        {--cc= : Override CC recipients (comma/semicolon/space separated)}
        {--weeks=8 : Number of week-on-week columns (current week included)}
        {--months=2 : Number of full prior months for month-on-month}
        {--top=15 : DSAs to list in the email body}
        {--dry-run : Print the numbers to the console instead of sending}
    ';

    protected $description = 'Email DSA account opening report (introducer CN…): WTD, MTD, month-on-month and week-on-week, with an Excel attachment.';

    public function handle(DsaAccountOpeningService $service): int
    {
        $asOf = Carbon::parse((string) ($this->argument('date') ?: today()->toDateString()))->toDateString();

        $report = $service->build(
            $asOf,
            max(1, (int) $this->option('weeks')),
            max(0, (int) $this->option('months'))
        );

        if ($this->option('dry-run')) {
            $this->printReport($report);
            return self::SUCCESS;
        }

        $toOpt = (string) ($this->option('to') ?? '');
        $to = $toOpt !== ''
            ? $this->parseEmails($toOpt)
            : $this->parseEmails(config('reports.dsa_accounts.to', []));

        if (empty($to)) {
            $this->error('No TO recipients configured. Set reports.dsa_accounts.to or pass --to=');
            return self::FAILURE;
        }

        $ccOpt = (string) ($this->option('cc') ?? '');
        $cc = $ccOpt !== ''
            ? $this->parseEmails($ccOpt)
            : $this->parseEmails(config('reports.dsa_accounts.cc', []));

        if ($report['accounts']->isEmpty()) {
            $this->warn("No DSA-introduced accounts found in the report window ending {$asOf} — sending anyway.");
        }

        $mailable = new DsaAccountOpeningReportMail($report, max(1, (int) $this->option('top')));

        $mailable->attachData(
            Excel::raw(new DsaAccountOpeningWorkbookExport($report), ExcelWriter::XLSX),
            "DSA_Account_Opening_{$asOf}.xlsx",
            ['mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );

        Mail::to($to)->cc($cc)->send($mailable);

        $this->info('DSA account opening email sent (with Excel attachment).');
        $this->line('TO: ' . implode(', ', $to));
        $this->line('CC: ' . (empty($cc) ? '(none)' : implode(', ', $cc)));
        $this->line("As at {$asOf} | WTD: {$report['totals']->wtd_accounts} | MTD: {$report['totals']->mtd_accounts} | DSAs: {$report['dsaRows']->count()}");

        return self::SUCCESS;
    }

    private function printReport(array $report): void
    {
        $t = $report['totals'];

        $this->info("DSA account opening as at {$report['asOf']} (WTD from {$report['wtdStart']}, MTD from {$report['mtdStart']})");
        $this->line("WTD: {$t->wtd_accounts} accounts / {$t->wtd_customers} customers | MTD: {$t->mtd_accounts} accounts / {$t->mtd_customers} customers");
        $this->line('Deposits as at ' . ($report['depositDate'] ?? 'n/a') . ' | Loans as at ' . ($report['loanDate'] ?? 'n/a'));

        $money = fn ($v) => number_format((float) $v, 0);
        $row   = fn ($d) => [$d->dsa_code, $d->dsa_name, $d->wtd_accounts, $d->mtd_accounts, ...array_values($d->months), $d->book_accounts, $money($d->deposits), $money($d->loans)];

        $this->newLine();
        $this->table(
            ['DSA', 'Name', 'WTD', 'MTD', ...array_column($report['months'], 'label'), 'Accts (all)', 'Deposits', 'Loans'],
            [...$report['dsaRows']->map($row)->all(), $row($t)]
        );

        $this->table(
            array_column($report['weeks'], 'label'),
            [array_values($t->weeks)]
        );
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
