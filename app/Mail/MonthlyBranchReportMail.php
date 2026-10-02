<?php

declare(strict_types=1);

namespace App\Mail;

use App\Exports\Finance\MonthlyBranchWorkbookExport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class MonthlyBranchReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array $report MonthlyPerformanceReportService::buildBranches() result */
    public function __construct(public array $report) {}

    public function build(): static
    {
        $binary = Excel::raw(new MonthlyBranchWorkbookExport($this->report), ExcelWriter::XLSX);

        return $this->subject("{$this->report['label']} Branch Performance Report")
            ->view('emails.finance.monthly_branch_report')
            ->with(['report' => $this->report])
            ->attachData($binary, "Monthly_Branch_Performance_{$this->report['month']}.xlsx", [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }
}
