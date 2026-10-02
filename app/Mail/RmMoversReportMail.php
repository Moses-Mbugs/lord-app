<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class RmMoversReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array<string, array{rmRows: \Illuminate\Support\Collection, totals: object}> $segmentsData
     *        keyed by segment name (Premier/Advantage/Direct/...), in display order
     * @param Collection $topGainers top deposit customer gainers across all segments combined
     * @param Collection $topLosers  top deposit customer losers across all segments combined
     */
    public function __construct(
        public string $start,
        public string $end,
        public array $segmentsData,
        public object $grandTotals,
        public Collection $topGainers = new Collection(),
        public Collection $topLosers = new Collection()
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
                'topGainers'   => $this->topGainers,
                'topLosers'    => $this->topLosers,
            ]);
    }
}
