<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BalancesImportStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param 'info'|'success'|'warning'|'error' $level
     * @param array<string,string> $details
     */
    public function __construct(
        public string $level,
        public string $heading,
        public string $message,
        public array $details = [],
        protected ?array $recipients = null,
    ) {
    }

    public function build()
    {
        return $this->to($this->recipients())
            ->subject("[Balances Import] {$this->heading}")
            ->view('emails.finance.balances-import-status')
            ->with([
                'level' => $this->level,
                'heading' => $this->heading,
                'statusMessage' => $this->message,
                'details' => $this->details,
            ]);
    }

    protected function recipients(): array
    {
        if ($this->recipients !== null) {
            return $this->recipients;
        }

        return array_values(array_filter(array_map('trim', preg_split(
            '/[,\n]+/',
            (string) config('reports.balances.import_status_to', '')
        ))));
    }
}
