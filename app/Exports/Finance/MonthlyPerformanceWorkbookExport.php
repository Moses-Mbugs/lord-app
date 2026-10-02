<?php

declare(strict_types=1);

namespace App\Exports\Finance;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Monthly performance workbook: Summary (deposits Bank/LCY/FCY + loans by segment),
 * Branches, and the month's top CIF gainers/losers for deposits and loans.
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
              . ', YTD from ' . $this->d($loans['periods']['ytd_start'])
            : "Loans: no loan snapshot for {$loans['missing']}";

        $summaryBlocks = [
            $this->segmentBlock('Deposits — Bank (all currencies, KES equivalent)', $dep['bank'], 'Deposits'),
            $this->segmentBlock('Deposits — LCY (KES)', $dep['lcy'], 'Deposits'),
            $this->segmentBlock('Deposits — FCY (KES equivalent)', $dep['fcy'], 'Deposits'),
            $this->segmentBlock('Loans — Performing book (KES equivalent)', $loans['segments'], 'Loans'),
        ];

        return [
            new MonthlyPerformanceSheet('Summary', $title, "{$depSub} · {$loanSub}", $summaryBlocks, ['B', 'C', 'D'], ['B', 'C', 'E', 'F']),
            new MonthlyPerformanceSheet('Branches', $title, $depSub . ' · NTB on calendar account-open dates · P50 excluded', [$this->branchBlock()], ['C', 'D', 'F', 'G'], ['C', 'D', 'E', 'F', 'G', 'H', 'I', 'J']),
            new MonthlyPerformanceSheet('Deposit Top Movers', $title, $depSub, $this->cifBlocks('Deposits', $dep['top']), ['H'], ['F', 'G', 'H']),
            new MonthlyPerformanceSheet('Loan Top Movers', $title, $loanSub, $this->cifBlocks('Loans', $loans['top']), ['H'], ['F', 'G', 'H']),
        ];
    }

    private function segmentBlock(string $heading, array $segments, string $balanceLabel): array
    {
        $rows = $totalRows = $subRows = [];

        foreach ($segments as $seg) {
            if (($seg['code'] ?? '') === 'ALL') $totalRows[] = count($rows);
            $rows[] = $this->segmentRow(strtoupper((string) $seg['name']), $seg);

            foreach ($seg['sub_segments'] ?? [] as $sub) {
                $subRows[] = count($rows);
                $rows[]    = $this->segmentRow((string) $sub['name'], $sub);
            }
        }

        return [
            'heading'   => $heading,
            'headers'   => ['Segment', 'Month Δ', 'YTD Δ', 'Month Δ %', "Opening {$balanceLabel}", "Closing {$balanceLabel}"],
            'rows'      => $rows,
            'totalRows' => $totalRows,
            'subRows'   => $subRows,
        ];
    }

    private function segmentRow(string $name, array $s): array
    {
        $mv      = (float) $s['month_mv'];
        $closing = (float) $s['balance'];
        $opening = $closing - $mv;

        return [$name, $mv, (float) $s['ytd_mv'], $opening > 0 ? round($mv / $opening * 100, 2) . '%' : '—', $opening, $closing];
    }

    private function branchBlock(): array
    {
        $rows = $totalRows = [];

        foreach ($this->report['branches']['rows'] as $b) {
            if ($b['code'] === 'ALL') $totalRows[] = count($rows);
            $rows[] = [
                $b['code'], $b['code'] === 'ALL' ? 'TOTAL' : $b['name'],
                $b['dep_month'], $b['dep_ytd'], $b['dep_balance'],
                $b['loan_month'], $b['loan_ytd'], $b['loan_balance'],
                $b['ntb_month'], $b['ntb_ytd'],
            ];
        }

        return [
            'heading'   => 'Branch performance (Loans exclude the Corporate segment)',
            'headers'   => ['Branch Code', 'Branch Name', 'Deposits Month Δ', 'Deposits YTD Δ', 'Deposits Closing', 'Loans Month Δ', 'Loans YTD Δ', 'Loans Closing', 'NTB Month', 'NTB YTD'],
            'rows'      => $rows,
            'totalRows' => $totalRows,
        ];
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
