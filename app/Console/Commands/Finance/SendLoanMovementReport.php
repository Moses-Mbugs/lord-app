<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Mail\LoanMovementReportMail;
use App\Services\Reports\LoanMovementService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendLoanMovementReport extends Command
{
    protected $signature = 'report:send-loan-movement
                            {start : Start date YYYY-MM-DD}
                            {end   : End date YYYY-MM-DD}
                            {--to=  : Override TO recipients (comma-separated)}
                            {--cc=  : Override CC recipients (comma-separated)}';

    protected $description = 'Build LCY/FCY loan book movement report and send via email';

    public function handle(LoanMovementService $service): int
    {
        $start = Carbon::parse($this->argument('start'))->toDateString();
        $end   = Carbon::parse($this->argument('end'))->toDateString();

        $this->info("Building loan movement report: {$start} → {$end}");

        try {
            $lcyData = $service->build($start, $end, 'LCY');
            $fcyData = $service->build($start, $end, 'FCY');

            $toOpt = $this->option('to');
            $ccOpt = $this->option('cc');

            $toList = $toOpt
                ? array_map('trim', explode(',', $toOpt))
                : (array) config('reports.loans.to', []);

            $ccList = $ccOpt
                ? array_map('trim', explode(',', $ccOpt))
                : (array) config('reports.loans.cc', []);

            $toList = array_filter($toList);
            $ccList = array_filter($ccList);

            if (empty($toList)) {
                $this->warn('No recipients configured. Add emails to config/reports.php under loans.to');
                return self::FAILURE;
            }

            Mail::to($toList)->cc($ccList)->send(new LoanMovementReportMail($lcyData, $fcyData, $start, $end));

            $this->info("Email sent to " . count($toList) . " recipient(s).");
            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
