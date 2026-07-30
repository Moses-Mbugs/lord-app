<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DepositMovementReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $lcyData,
        public array $fcyData,
        public string $start,
        public string $end,
        public ?string $excelPath = null
    ) {}

    public function build()
    {
        $mail = $this->subject("Deposit Movement Report {$this->start} → {$this->end}")
            ->view('emails.finance.deposit_movement_report')
            ->with([
                'lcyData' => $this->lcyData,
                'fcyData' => $this->fcyData,
                'start'   => $this->start,
                'end'     => $this->end,
            ]);

        if ($this->excelPath && file_exists($this->excelPath)) {
            $mail->attach($this->excelPath);
        }

        return $mail;
    }
}
