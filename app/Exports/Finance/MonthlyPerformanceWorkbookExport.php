<?php

declare(strict_types=1);

namespace App\Exports\Finance;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Monthly Loans & Deposits workbook: Deposits (Bank/LCY/FCY by segment), Loans (by
 * segment), and the month's top CIF gainers/losers for each. Branches have their own
 * workbook (MonthlyBranchWorkbookExport).
 */
class MonthlyPerformanceWorkbookExport implements WithMultipleSheets
{
    /** @param array $report MonthlyPerformanceReportService::build() result */
    public function __construct(private readonly array $report) {}

    public function sheets(): array
    {
        $r     = $this->report;
        $dep   = $r['deposits'];
        $loans = $r['loans'];
        $title = "ECOBANK KENYA — MONTHLY PERFORMANCE — {$r['label']}";

        $depSub  = 'Deposits: month ' . $this->range($dep['periods']['month_start'], $dep['periods']['month_end'])
                 . ', YTD from ' . $this->d($dep['periods']['ytd_start']);
        $loanSub = $loans['periods']
            ? 'Loans: month ' . $this->range($loans['periods']['month_start'], $loans['periods']['month_end'])
            : "Loans: no loan snapshot for {$loans['missing']}";

        $depositBlocks = [
            $this->segmentBlock('Deposits — Bank (all currencies, KES equivalent)', $dep['bank'], 'Deposits', true),
            $this->segmentBlock('Deposits — LCY (KES)', $dep['lcy'], 'Deposits', true),
            $this->segmentBlock('Deposits — FCY (KES equivalent)', $dep['fcy'], 'Deposits', true),
        ];

        // Loans are month-on-month only — not enough loan history yet for a meaningful YTD.
        $loanBlocks = [
            $this->segmentBlock('Loans — Performing book (KES equivalent)', $loans['segments'], 'Loans', false),
        ];

        return [
            // Deposit columns: A Segment, B Month Δ, C YTD Δ, D %, E Opening, F Closing
            new MonthlyPerformanceSheet('Deposits', $title, $depSub, $depositBlocks, ['B', 'C'], ['B', 'C', 'E', 'F']),
            // Loan columns:    A Segment, B Month Δ, C %, D Opening, E Closing
            new MonthlyPerformanceSheet('Loans', $title, $loanSub, $loanBlocks, ['B'], ['B', 'D', 'E']),
            new MonthlyPerformanceSheet('Deposit Top Movers', $title, $depSub, $this->cifBlocks('Deposits', $dep['top']), ['H'], ['F', 'G', 'H']),
            new MonthlyPerformanceSheet('Loan Top Movers', $title, $loanSub, $this->cifBlocks('Loans', $loans['top']), ['H'], ['F', 'G', 'H']),
        ];
    }

    private function segmentBlock(string $heading, array $segments, string $balanceLabel, bool $withYtd): array
    {
        $rows = $totalRows = $subRows = [];

        foreach ($segments as $seg) {
            if (($seg['code'] ?? '') === 'ALL') $totalRows[] = count($rows);
            $rows[] = $this->segmentRow(strtoupper((string) $seg['name']), $seg, $withYtd);

            foreach ($seg['sub_segments'] ?? [] as $sub) {
                $subRows[] = count($rows);
                $rows[]    = $this->segmentRow((string) $sub['name'], $sub, $withYtd);
            }
        }

        $headers = $withYtd
            ? ['Segment', 'Month Δ', 'YTD Δ', 'Month Δ %', "Opening {$balanceLabel}", "Closing {$balanceLabel}"]
            : ['Segment', 'Month Δ', 'Month Δ %', "Opening {$balanceLabel}", "Closing {$balanceLabel}"];

        return [
            'heading'   => $heading,
            'headers'   => $headers,
            'rows'      => $rows,
            'totalRows' => $totalRows,
            'subRows'   => $subRows,
        ];
    }

    private function segmentRow(string $name, array $s, bool $withYtd): array
    {
        $mv      = (float) $s['month_mv'];
        $closing = (float) $s['balance'];
        $opening = $closing - $mv;
        $pct     = $opening > 0 ? round($mv / $opening * 100, 2) . '%' : '—';

        return $withYtd
            ? [$name, $mv, (float) $s['ytd_mv'], $pct, $opening, $closing]
            : [$name, $mv, $pct, $opening, $closing];
    }

    private function cifBlocks(string $product, array $top): array
    {
        $map = fn($r) => [
            (string) ($r->cif ?? ''),
            (string) ($r->customer_name ?? ''),
            (string) ($r->branch_code ?? ''),
            (string) ($r->business_segment_name ?? $r->business_segment ?? ''),
            (string) ($r->sub_segment_name ?? ''),
            (float)  ($r->start_balance ?? 0),
            (float)  ($r->end_balance ?? 0),
            (float)  ($r->movement ?? 0),
        ];

        $headers = ['CIF', 'Customer', 'Branch', 'Segment', 'Sub-segment', 'Opening', 'Closing', 'Month Δ'];

        return [
            ['heading' => "{$product} — top gainers this month", 'headers' => $headers, 'rows' => collect($top['gainers'] ?? [])->map($map)->all()],
            ['heading' => "{$product} — top losers this month",  'headers' => $headers, 'rows' => collect($top['losers'] ?? [])->map($map)->all()],
        ];
    }

    private function d(?string $date): string
    {
        return $date ? Carbon::parse($date)->format('d M Y') : '—';
    }

    private function range(string $start, string $end): string
    {
        return $this->d($start) . ' → ' . $this->d($end);
    }
}
