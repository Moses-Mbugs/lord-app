<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RmMoversReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array<string, array{rmRows: \Illuminate\Support\Collection, totals: object, topGainers: \Illuminate\Support\Collection, topLosers: \Illuminate\Support\Collection}> $segmentsData
     *        keyed by segment name (Premier/Advantage/Direct/...), in display order
     */
    public function __construct(
        public string $start,
        public string $end,
        public array $segmentsData,
        public object $grandTotals
    ) {}

    public function build()
    {
        return $this->subject("RM Movers Report {$this->start} → {$this->end}")
            ->view('emails.finance.rm_movers_report')
            ->with([
                'start'        => $this->start,
                'end'          => $this->end,
                'segmentsData' => $this->segmentsData,
                'grandTotals'  => $this->grandTotals,
            ]);
    }
}
