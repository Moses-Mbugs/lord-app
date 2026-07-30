<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoanMovementReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $data,
        public string $start,
        public string $end,
        public ?string $excelPath = null
    ) {}

    public function build()
    {
        $mail = $this->subject("Loan Book Movement Report {$this->start} → {$this->end}")
            ->view('emails.finance.loan_movement')
            ->with([
                'data'  => $this->data,
                'start' => $this->start,
                'end'   => $this->end,
            ]);

        if ($this->excelPath && file_exists($this->excelPath)) {
            $mail->attach($this->excelPath);
        }

        return $mail;
    }
}
