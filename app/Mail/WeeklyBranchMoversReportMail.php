<?php

declare(strict_types=1);

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WeeklyBranchMoversReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $weekEnd,
        public array  $periods,
        public array  $data,
        public int    $limit = 10
    ) {}

    public function build(): static
    {
        $formatted = Carbon::parse($this->weekEnd)->format('d M Y');

        return $this->subject("Weekly Branch Movement — {$formatted}")
            ->view('emails.finance.weekly_branch_movers_report')
            ->with([
                'weekEnd' => $this->weekEnd,
                'periods' => $this->periods,
                'data'    => $this->data,
                'limit'   => $this->limit,
            ]);
    }
}
