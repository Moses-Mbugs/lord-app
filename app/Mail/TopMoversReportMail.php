<?php

declare(strict_types=1);

namespace App\Mail;

use App\Exports\Finance\TopMoversWorkbookExport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;

class TopMoversReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $start,
        public string $end,
        public array $grouped,
        public int $limit,
        public ?Collection $segments = null // ✅ new (segment_movers rows)
    ) {}

    public function build()
    {
        $fileName = "Top_Movers_{$this->start}_to_{$this->end}.xlsx";

        // Generate workbook in-memory (still only movers sheets for now)
        $binary = Excel::raw(
            new TopMoversWorkbookExport($this->grouped, $this->start, $this->end, $this->segments ?? collect()),
            ExcelWriter::XLSX
        );

        return $this->subject("Daily Deposits Movement Report {$this->start} → {$this->end}")
            ->view('emails.finance.top_movers_report')
            ->with([
                'start'    => $this->start,
                'end'      => $this->end,
                'grouped'  => $this->grouped,
                'limit'    => $this->limit,
                'segments' => $this->segments ?? collect(), // ✅ new
            ])
            ->attachData($binary, $fileName, [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }
}
