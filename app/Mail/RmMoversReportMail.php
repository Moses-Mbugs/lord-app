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

    public function __construct(
        public string $start,
        public string $end,
        public Collection $rmRows,
        public object $totals,
        public Collection $topGainers = new Collection(),
        public Collection $topLosers = new Collection()
    ) {}

    public function build()
    {
        return $this->subject("RM Movers Report {$this->start} → {$this->end}")
            ->view('emails.finance.rm_movers_report')
            ->with([
                'start'      => $this->start,
                'end'        => $this->end,
                'rmRows'     => $this->rmRows,
                'totals'     => $this->totals,
                'topGainers' => $this->topGainers,
                'topLosers'  => $this->topLosers,
            ]);
    }
}
