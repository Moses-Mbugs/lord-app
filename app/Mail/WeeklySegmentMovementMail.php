<?php

declare(strict_types=1);

namespace App\Mail;

use App\Exports\Finance\WeeklySegmentWorkbookExport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;

class WeeklySegmentMovementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $weekEnd,
        public array  $data,
        public array  $drilldown,
        public array  $historicalSection,
        public array  $toList,
        public array  $ccList = []
    ) {}

    public function build(): static
    {
        $fileName = "Weekly_Segment_Movement_{$this->weekEnd}.xlsx";

        $binary = Excel::raw(
            new WeeklySegmentWorkbookExport(
                $this->data,
                $this->drilldown,
                $this->historicalSection
            ),
            ExcelWriter::XLSX
        );

        return $this->subject("Weekly Deposits Movement Report – w/e {$this->weekEnd}")
            ->view('emails.finance.weekly_segment_movement')
            ->with([
                'weekEnd'          => $this->weekEnd,
                'data'             => $this->data,
                'historicalSection' => $this->historicalSection,
            ])
            ->attachData($binary, $fileName, [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }
}
