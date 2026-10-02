<?php

declare(strict_types=1);

namespace App\Exports\Finance;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Monthly branch workbook: per-branch Deposits (MoM + YTD), Loans (MoM) and NTB,
 * plus the month's top/bottom branches by deposit movement.
 */
class MonthlyBranchWorkbookExport implements WithMultipleSheets
{
    /** @param array $report MonthlyPerformanceReportService::buildBranches() result */
    public function __construct(private readonly array $report) {}

    public function sheets(): array
    {
        $r    = $this->report;
        $dep  = $r['deposit_periods'];
        $loan = $r['loan_periods'];
        $d    = fn(?string $date) => $date ? Carbon::parse($date)->format('d M Y') : '—';

        $subtitle = "Deposits: month {$d($dep['month_start'])} → {$d($dep['month_end'])}, YTD from {$d($dep['ytd_start'])}"
            . ($loan ? " · Loans: month {$d($loan['month_start'])} → {$d($loan['month_end'])}" : ' · Loans: no snapshot')
            . ' · NTB on calendar account-open dates · P50 excluded';

        $rows = $totalRows = [];
        foreach ($r['rows'] as $b) {
            if ($b['code'] === 'ALL') $totalRows[] = count($rows);
            $rows[] = [
                $b['code'], $b['code'] === 'ALL' ? 'TOTAL' : $b['name'],
                $b['dep_month'], $b['dep_ytd'], $b['dep_balance'],
                $b['loan_month'], $b['loan_balance'],
                $b['ntb_month'], $b['ntb_ytd'],
            ];
        }

        $branchBlock = [
            'heading'   => 'Branch performance (Loans exclude the Corporate segment)',
            'headers'   => ['Branch Code', 'Branch Name', 'Deposits Month Δ', 'Deposits YTD Δ', 'Deposits Closing', 'Loans Month Δ', 'Loans Closing', 'NTB Month', 'NTB YTD'],
            'rows'      => $rows,
            'totalRows' => $totalRows,
        ];

        $topMap  = fn($t) => [(int) ($t->rank ?? 0), (string) ($t->group_key ?? ''), (string) ($t->group_name ?? ''), (float) ($t->start_balance ?? 0), (float) ($t->end_balance ?? 0), (float) ($t->movement ?? 0)];
        $topHdr  = ['Rank', 'Branch Code', 'Branch Name', 'Opening', 'Closing', 'Deposits Month Δ'];
        $topBlocks = [
            ['heading' => 'Top branches by deposit growth this month',    'headers' => $topHdr, 'rows' => collect($r['top']['month']['gainers'] ?? [])->map($topMap)->all()],
            ['heading' => 'Bottom branches by deposit movement this month', 'headers' => $topHdr, 'rows' => collect($r['top']['month']['losers'] ?? [])->map($topMap)->all()],
        ];

        $title = "ECOBANK KENYA — MONTHLY BRANCH PERFORMANCE — {$r['label']}";

        return [
            new MonthlyPerformanceSheet('Branches', $title, $subtitle, [$branchBlock], ['C', 'D', 'F'], ['C', 'D', 'E', 'F', 'G', 'H', 'I']),
            new MonthlyPerformanceSheet('Top & Bottom Branches', $title, $subtitle, $topBlocks, ['F'], ['D', 'E', 'F']),
        ];
    }
}
