<?php

declare(strict_types=1);

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WeeklyRmMoversReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array $periods ['week'|'mtd'|'ytd' => ['start','end','label']]
     * @param array<string, array> $segmentsData keyed by segment name, each value shaped
     *        like ['week'|'mtd'|'ytd' => ['period','summary','all'], 'week' additionally
     *        carrying 'topGainers'/'topLosers']
     * @param array<string, object> $grandData 'week'|'mtd'|'ytd' => 'all'-shaped object,
     *        summed across every segment, for the top-level KPI strip
     */
    public function __construct(
        public string $weekEnd,
        public array  $periods,
        public array  $segmentsData,
        public array  $grandData,
        public int    $limit = 10
    ) {}

    public function build(): static
    {
        $formatted = Carbon::parse($this->weekEnd)->format('d M Y');

        return $this->subject("Weekly RM Movement — {$formatted}")
            ->view('emails.finance.weekly_rm_movers_report')
            ->with([
                'weekEnd'      => $this->weekEnd,
                'periods'      => $this->periods,
                'segmentsData' => $this->segmentsData,
                'grandData'    => $this->grandData,
                'limit'        => $this->limit,
            ]);
    }
}
