<?php

declare(strict_types=1);

namespace App\Mail;

use App\Exports\Finance\WeeklyLoanWorkbookExport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;

class WeeklyLoanMovementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $weekEnd,
        public array  $data,
        public array  $drilldown,
        public array  $toList,
        public array  $ccList = [],
        public array  $weekTopMovers = ['gainers' => [], 'losers' => []],
        public array  $mtdTopMovers = ['gainers' => [], 'losers' => []],
        public array  $monthlyMovement = ['monthLabels' => [], 'segments' => []]
    ) {}

    public function build(): static
    {
        $fileName = "Weekly_Performing_Loan_Movement_{$this->weekEnd}.xlsx";

        $binary = Excel::raw(
            new WeeklyLoanWorkbookExport(
                $this->data,
                $this->drilldown,
                $this->weekTopMovers,
                $this->mtdTopMovers,
                $this->monthlyMovement
            ),
            ExcelWriter::XLSX
        );

        return $this->subject("Weekly Performing Loans Movement Report – w/e {$this->weekEnd}")
            ->view('emails.finance.weekly_loan_movement')
            ->with([
                'weekEnd' => $this->weekEnd,
                'data'    => $this->data,
            ])
            ->attachData($binary, $fileName, [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }
}
