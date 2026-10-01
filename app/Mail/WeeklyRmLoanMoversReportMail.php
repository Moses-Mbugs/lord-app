<?php

declare(strict_types=1);

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WeeklyRmLoanMoversReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array $periods ['week'|'mtd'|'ytd' => ['start','end','label']]
     * @param array<string, array> $segmentsData keyed by segment name
     * @param array<string, object> $grandData 'week'|'mtd'|'ytd' => 'all'-shaped object,
     *        summed across every segment, for the top-level KPI strip
     */
    public function __construct(
        public string $weekEnd,
        public array  $periods,
        public array  $segmentsData,
        public array  $grandData
    ) {}

    public function build(): static
    {
        $formatted = Carbon::parse($this->weekEnd)->format('d M Y');

        return $this->subject("Weekly RM Loan Movement — {$formatted}")
            ->view('emails.finance.weekly_rm_loan_movers_report')
            ->with([
                'weekEnd'      => $this->weekEnd,
                'periods'      => $this->periods,
                'segmentsData' => $this->segmentsData,
                'grandData'    => $this->grandData,
            ]);
    }
}
