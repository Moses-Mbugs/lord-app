<?php

declare(strict_types=1);

namespace App\Mail;

use App\Exports\Finance\MonthlyPerformanceWorkbookExport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class MonthlyPerformanceReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array $report MonthlyPerformanceReportService::build() result */
    public function __construct(public array $report) {}

    public function build(): static
    {
        $binary = Excel::raw(new MonthlyPerformanceWorkbookExport($this->report), ExcelWriter::XLSX);

        return $this->subject("Monthly Bank Performance – {$this->report['label']}")
            ->view('emails.finance.monthly_performance_report')
            ->with(['report' => $this->report])
            ->attachData($binary, "Monthly_Performance_{$this->report['month']}.xlsx", [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }
}
