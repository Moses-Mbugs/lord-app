<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class BranchMoversReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $start,
        public string $end,
        public Collection $summaryRows,
        public Collection $topGainers,
        public Collection $topLosers,
        public int $limit
    ) {}

    public function build()
    {
        return $this->subject("Branch Movers Report {$this->start} → {$this->end}")
            ->view('emails.finance.branch_movers_report')
            ->with([
                'start'       => $this->start,
                'end'         => $this->end,
                'summaryRows' => $this->summaryRows,
                'topGainers'  => $this->topGainers,
                'topLosers'   => $this->topLosers,
                'limit'       => $this->limit,
            ]);
    }
}

