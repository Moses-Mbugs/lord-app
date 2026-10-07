<?php

declare(strict_types=1);

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class WeeklyRmMoversReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array $periods ['week'|'mtd'|'ytd' => ['start','end','label']]
     * @param array<string, array> $segmentsData keyed by segment name, each value shaped
     *        like ['week'|'mtd'|'ytd' => ['period','summary','all']]
     * @param array<string, object> $grandData 'week'|'mtd'|'ytd' => 'all'-shaped object,
     *        summed across every segment, for the top-level KPI strip
     * @param Collection $topGainers top deposit customer gainers (week period) across all segments combined
     * @param Collection $topLosers  top deposit customer losers (week period) across all segments combined
     * @param array<string, array{rows: Collection, totals: object}> $budgetData keyed by
     *        segment name, deposit/NTB actual vs FY target — empty array if no RM in scope
     *        has a target recorded for $targetYear
     * @param object|null $budgetGrand same shape as a segment's totals, summed across all segments
     */
    public function __construct(
        public string $weekEnd,
        public array  $periods,
        public array  $segmentsData,
        public array  $grandData,
        public int    $limit = 10,
        public Collection $topGainers = new Collection(),
        public Collection $topLosers = new Collection(),
        public int $targetYear = 0,
        public array $budgetData = [],
        public ?object $budgetGrand = null
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
                'topGainers'   => $this->topGainers,
                'topLosers'    => $this->topLosers,
                'targetYear'   => $this->targetYear,
                'budgetData'   => $this->budgetData,
                'budgetGrand'  => $this->budgetGrand,
            ]);
    }
}
