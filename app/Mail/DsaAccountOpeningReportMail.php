<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DsaAccountOpeningReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array $report DsaAccountOpeningService::build()
     * @param int   $topLimit DSAs to list in the email body (the attachment has all of them)
     */
    public function __construct(
        public array $report,
        public int $topLimit = 15
    ) {}

    public function build()
    {
        $asOf = \Carbon\Carbon::parse($this->report['asOf'])->format('d M Y');

        $subject = ($this->report['newStart'] ?? null) !== null
            ? "DSA Daily Report — {$asOf}"
            : 'DSA Weekly Report — week ending ' . $asOf;

        return $this->subject($subject)
            ->view('emails.finance.dsa_account_opening_report')
            ->with([
                'report'   => $this->report,
                'topLimit' => $this->topLimit,
            ]);
    }
}
