<?php

declare(strict_types=1);

namespace App\Notifications\Finance;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;

class BranchMoversReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $start,
        public string $end,
        public Collection $summaryRows,
        public Collection $topGainers,
        public Collection $topLosers,
        public int $limit
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Branch Movers Report {$this->start} → {$this->end}")
            ->view('emails.finance.branch_movers_report', [
                'start'       => $this->start,
                'end'         => $this->end,
                'summaryRows' => $this->summaryRows,
                'topGainers'  => $this->topGainers,
                'topLosers'   => $this->topLosers,
                'limit'       => $this->limit,
            ]);
    }
}