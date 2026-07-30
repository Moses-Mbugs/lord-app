<?php

namespace App\Console\Commands\Finance;

use Illuminate\Support\Facades\Mail;
use App\Models\Finance\ReportRun;
use App\Services\Reports\DepositMovementReportService;
use App\Services\Reports\TopMoversService;
use App\Mail\DepositMovementReportMail;
use Carbon\Carbon;
use Illuminate\Console\Command;


class SendDepositMovementReport extends Command
{
    protected $signature = 'report:send-deposit-movement {start} {end}';
    protected $description = 'Send LCY/FCY deposit movement report email (with optional top movers excel)';

    public function handle(
        DepositMovementReportService $reportService,
        TopMoversService $topMoversService
    ): int {
        $start = Carbon::parse($this->argument('start'))->toDateString();
        $end   = Carbon::parse($this->argument('end'))->toDateString();

        $run = ReportRun::create([
            'report_type' => 'deposit_movement',
            'start_date' => $start,
            'end_date' => $end,
            'status' => 'generated',
            'recipients' => config('reports.deposit_movement.to'),
        ]);

        try {
            // Build dataset for helper
            $lcyData = $reportService->build($start, $end, 'LCY');
            $fcyData = $reportService->build($start, $end, 'FCY');

            // Build top movers rows (so Excel can read from table)
            $topMoversService->build($start, $end, 'LCY');
            $topMoversService->build($start, $end, 'FCY');

            // If you already implemented Excel exports, generate & attach here:
            $excelPath = null;


            $to = (array) config('reports.balances.top_movers_to', []);
            $cc = (array) config('reports.balances.top_movers_cc', []);

            Mail::to($to)
                ->cc($cc)
                ->send(new DepositMovementReportMail(
                    $lcyData,
                    $fcyData,
                    $start,
                    $end,
                    $excelPath
                ));

            $run->update([
                'status' => 'sent',
                'excel_path' => $excelPath,
                'sent_at' => now(),
            ]);

            $this->info("Report sent for {$start} → {$end}");
            return self::SUCCESS;

        } catch (\Throwable $e) {
            $run->update(['status' => 'failed', 'error' => $e->getMessage()]);
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
