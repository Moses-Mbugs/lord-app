<?php

declare(strict_types=1);

namespace App\Exports\Finance;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WeeklyBranchMoversWorkbookExport implements WithMultipleSheets
{
    public function __construct(
        private readonly string $weekEnd,
        private readonly array  $periods,
        private readonly array  $data,
        private readonly int    $limit = 10
    ) {}

    public function sheets(): array
    {
        $weekData = $this->data['week'] ?? [];
        $mtdData  = $this->data['mtd']  ?? [];
        $ytdData  = $this->data['ytd']  ?? [];

        return [
            new WeeklyBranchOverviewSheet($this->weekEnd, $this->periods, $this->data),
            new WeeklyBranchPeriodSheet('Weekly',  $this->periods['week'], $weekData, $this->limit),
            new WeeklyBranchPeriodSheet('MTD',     $this->periods['mtd'],  $mtdData,  $this->limit),
            new WeeklyBranchPeriodSheet('YTD',     $this->periods['ytd'],  $ytdData,  $this->limit),
        ];
    }
}

/**
 * SHEET 1: Multi-period overview — all branches × (Weekly Δ, MTD Δ, YTD Δ, Loan Weekly Δ)
 */
class WeeklyBranchOverviewSheet implements FromArray, WithTitle, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    private array $boldRows = [];

    public function __construct(
        private readonly string $weekEnd,
        private readonly array  $periods,
        private readonly array  $data
    ) {}

    public function title(): string { return 'Overview'; }

    public function array(): array
    {
        $weekPeriod = $this->periods['week'] ?? [];
        $mtdPeriod  = $this->periods['mtd']  ?? [];
        $ytdPeriod  = $this->periods['ytd']  ?? [];

        // Build branch map: code → [name, end_balance, week_mv, mtd_mv, ytd_mv, loan_week_mv, loan_mtd_mv, loan_ytd_mv, week_ntb, mtd_ntb, ytd_ntb]
        $map = [];

        foreach (['week', 'mtd', 'ytd'] as $key) {
            $summary = collect($this->data[$key]['summary'] ?? []);
            foreach ($summary as $r) {
                $code = strtoupper(trim((string) ($r->group_key ?? '')));
                if ($code === '') continue;
                if (!isset($map[$code])) {
                    $map[$code] = [
                        'code'         => $code,
                        'name'         => (string) ($r->group_name ?? $code),
                        'end_balance'  => 0,
                        'week_mv'      => 0,
                        'mtd_mv'       => 0,
                        'ytd_mv'       => 0,
                        'week_loan_mv' => 0,
                        'mtd_loan_mv'  => 0,
                        'ytd_loan_mv'  => 0,
                        'week_ntb'     => 0,
                        'mtd_ntb'      => 0,
                        'ytd_ntb'      => 0,
                    ];
                }
                $map[$code]["{$key}_mv"]      = (float) ($r->movement      ?? 0);
                $map[$code]["{$key}_loan_mv"]  = (float) ($r->loan_movement ?? 0);
                $map[$code]["{$key}_ntb"]      = (int)   ($r->ntb_count     ?? 0);
                if ($key === 'week') {
                    $map[$code]['end_balance'] = (float) ($r->end_balance ?? 0);
                    $map[$code]['name']        = (string) ($r->group_name ?? $code);
                }
            }
        }

        // Sort: regular branches (P-prefix or others), then 834, 950, ALL
        uksort($map, function ($a, $b) {
            $special = ['834' => 1, '950' => 2, 'ALL' => 99];
            $as = $special[$a] ?? 0;
            $bs = $special[$b] ?? 0;
            if ($as !== $bs) return $as - $bs;
            return strcmp($a, $b);
        });

        $rows    = [];
        $rowNum  = 0;

        // Title
        $rows[] = ['ECOBANK KENYA — WEEKLY BRANCH MOVEMENT OVERVIEW', '', '', '', '', '', '', '', '', '', '', ''];
        $this->boldRows[] = ++$rowNum;

        $rows[] = ["Week ending: {$this->weekEnd}", '', '', '', '', '', '', '', '', '', '', ''];
        ++$rowNum;

        $rows[] = [
            "Weekly: {$weekPeriod['start']} → {$weekPeriod['end']}",
            "MTD: {$mtdPeriod['start']} → {$mtdPeriod['end']}",
            "YTD: {$ytdPeriod['start']} → {$ytdPeriod['end']}",
            '', '', '', '', '', '', '', '', '',
        ];
        ++$rowNum;

        $rows[] = ['', '', '', '', '', '', '', '', '', '', '', ''];
        ++$rowNum;

        // Column header
        $headerRow = ++$rowNum;
        $rows[] = [
            'Branch Code',
            'Branch Name',
            'End Balance',
            'Weekly Δ (Dep)',
            'MTD Δ (Dep)',
            'YTD Δ (Dep)',
            'Weekly Δ (Loans)',
            'MTD Δ (Loans)',
            'YTD Δ (Loans)',
            'Weekly NTB',
            'MTD NTB',
            'YTD NTB',
        ];
        $this->boldRows[] = $headerRow;

        foreach ($map as $b) {
            ++$rowNum;
            $rows[] = [
                (string) $b['code'],
                (string) $b['name'],
                (float)  $b['end_balance'],
                (float)  $b['week_mv'],
                (float)  $b['mtd_mv'],
                (float)  $b['ytd_mv'],
                (float)  $b['week_loan_mv'],
                (float)  $b['mtd_loan_mv'],
                (float)  $b['ytd_loan_mv'],
                (int)    $b['week_ntb'],
                (int)    $b['mtd_ntb'],
                (int)    $b['ytd_ntb'],
            ];
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'J' => NumberFormat::FORMAT_NUMBER,
            'K' => NumberFormat::FORMAT_NUMBER,
            'L' => NumberFormat::FORMAT_NUMBER,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet   = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                // Title row
                $sheet->getStyle('A1:L1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '002E4A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);
                $sheet->mergeCells('A1:L1');
                $sheet->getRowDimension(1)->setRowHeight(26);

                // Period info rows
                $sheet->mergeCells('A2:L2');
                $sheet->mergeCells('A3:C3');

                // Column header row (row 5)
                $headerRow = 5;
                $sheet->getStyle("A{$headerRow}:L{$headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3A5F']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("A{$headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Loan columns — green tint header
                $sheet->getStyle("G{$headerRow}:I{$headerRow}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '166534']],
                ]);

                // NTB columns — amber tint header
                $sheet->getStyle("J{$headerRow}:L{$headerRow}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B45309']],
                ]);

                // Data rows — colour movement columns by sign, amber-tint NTB columns (plain counts, not gain/loss)
                if ($lastRow > $headerRow) {
                    for ($row = $headerRow + 1; $row <= $lastRow; $row++) {
                        foreach (['D', 'E', 'F', 'G', 'H', 'I'] as $col) {
                            $v = $sheet->getCell("{$col}{$row}")->getValue();
                            if (!is_numeric($v)) continue;
                            $vf = (float) $v;
                            if ($vf > 0)     $sheet->getStyle("{$col}{$row}")->getFont()->getColor()->setRGB('0B6E4F');
                            elseif ($vf < 0) $sheet->getStyle("{$col}{$row}")->getFont()->getColor()->setRGB('B00020');
                        }
                        // Light green tint on loan columns
                        $sheet->getStyle("G{$row}:I{$row}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
                        ]);
                        // Light amber tint + font colour on NTB columns
                        $sheet->getStyle("J{$row}:L{$row}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']],
                            'font' => ['color' => ['rgb' => '92400E']],
                        ]);
                    }

                    // ALL row (last data row) — bold + light grey
                    $sheet->getStyle("A{$lastRow}:L{$lastRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                    ]);
                }

                $sheet->freezePane('A6');
            },
        ];
    }
}

/**
 * SHEET 2/3/4: Single-period detail — Branch × (Opening, Closing, Dep Δ, Loan Open, Loan Close, Loan Δ)
 */
class WeeklyBranchPeriodSheet implements FromArray, WithTitle, ShouldAutoSize, WithColumnFormatting, WithStyles, WithEvents
{
    private array $boldRows = [];

    public function __construct(
        private readonly string $label,
        private readonly array  $period,
        private readonly array  $periodData,
        private readonly int    $limit = 10
    ) {}

    public function title(): string { return $this->label; }

    public function headings(): array
    {
        return ['Branch Code', 'Branch Name', 'Start Balance', 'End Balance', 'Dep Movement', 'Loan Opening', 'Loan Closing', 'Loan Movement', 'NTB'];
    }

    public function array(): array
    {
        $summary    = collect($this->periodData['summary']    ?? []);
        $topGainers = collect($this->periodData['topGainers'] ?? []);
        $topLosers  = collect($this->periodData['topLosers']  ?? []);

        $start = $this->period['start'] ?? '—';
        $end   = $this->period['end']   ?? '—';
        $label = $this->label;

        $rows   = [];
        $rowNum = 0;

        // Title
        $rows[] = ["ECOBANK KENYA — {$label} BRANCH MOVEMENTS  ({$start} → {$end})", '', '', '', '', '', '', ''];
        $this->boldRows[] = ++$rowNum;

        $rows[] = ['', '', '', '', '', '', '', ''];
        ++$rowNum;

        // Summary section header
        $rows[] = ['BRANCH SUMMARY', '', '', '', '', '', '', ''];
        $this->boldRows[] = ++$rowNum;

        $this->boldRows[] = ++$rowNum;
        $rows[] = $this->headings();

        if ($summary->isEmpty()) {
            $rows[] = ['(no data)', '', '', '', '', '', '', ''];
            ++$rowNum;
        } else {
            foreach ($summary as $r) {
                $rows[] = [
                    (string) ($r->group_key     ?? ''),
                    (string) ($r->group_name    ?? ''),
                    (float)  ($r->start_balance ?? 0),
                    (float)  ($r->end_balance   ?? 0),
                    (float)  ($r->movement      ?? 0),
                    (float)  ($r->loan_open      ?? 0),
                    (float)  ($r->loan_close     ?? 0),
                    (float)  ($r->loan_movement  ?? 0),
                    (int)    ($r->ntb_count      ?? 0),
                ];
                ++$rowNum;
            }
        }

        $rows[] = ['', '', '', '', '', '', '', ''];
        ++$rowNum;

        // Top gainers section
        $gHeaderRow = ++$rowNum;
        $rows[] = ["TOP {$this->limit} GAINERS", '', '', '', '', '', '', ''];
        $this->boldRows[] = $gHeaderRow;

        $gTableRow = ++$rowNum;
        $rows[] = ['Rank', 'Branch Code', 'Branch Name', 'Start Balance', 'End Balance', 'Movement', '', ''];
        $this->boldRows[] = $gTableRow;

        if ($topGainers->isEmpty()) {
            $rows[] = ['', '(no data)', '', '', '', '', '', ''];
            ++$rowNum;
        } else {
            foreach ($topGainers as $r) {
                $rows[] = [
                    (int)    ($r->rank          ?? 0),
                    (string) ($r->group_key     ?? ''),
                    (string) ($r->group_name    ?? ''),
                    (float)  ($r->start_balance ?? 0),
                    (float)  ($r->end_balance   ?? 0),
                    (float)  ($r->movement      ?? 0),
                    '', '',
                ];
                ++$rowNum;
            }
        }

        $rows[] = ['', '', '', '', '', '', '', ''];
        ++$rowNum;

        // Top losers section
        $lHeaderRow = ++$rowNum;
        $rows[] = ["TOP {$this->limit} LOSERS", '', '', '', '', '', '', ''];
        $this->boldRows[] = $lHeaderRow;

        $lTableRow = ++$rowNum;
        $rows[] = ['Rank', 'Branch Code', 'Branch Name', 'Start Balance', 'End Balance', 'Movement', '', ''];
        $this->boldRows[] = $lTableRow;

        if ($topLosers->isEmpty()) {
            $rows[] = ['', '(no data)', '', '', '', '', '', ''];
            ++$rowNum;
        } else {
            foreach ($topLosers as $r) {
                $rows[] = [
                    (int)    ($r->rank          ?? 0),
                    (string) ($r->group_key     ?? ''),
                    (string) ($r->group_name    ?? ''),
                    (float)  ($r->start_balance ?? 0),
                    (float)  ($r->end_balance   ?? 0),
                    (float)  ($r->movement      ?? 0),
                    '', '',
                ];
                ++$rowNum;
            }
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet   = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                // Title row — dark navy
                $sheet->mergeCells('A1:I1');
                $sheet->getStyle('A1:I1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '002E4A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(24);

                foreach ($this->boldRows as $r) {
                    $sheet->getStyle("A{$r}:I{$r}")->getFont()->setBold(true);
                }

                // Loan columns — green tint
                if ($lastRow > 4) {
                    $sheet->getStyle("F4:H{$lastRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
                        'font' => ['color' => ['rgb' => '166534']],
                    ]);
                    // loan header cells styled in bold rows (row 4 is the summary header)
                    $sheet->getStyle("F4:H4")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '14532D']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCFCE7']],
                    ]);

                    // NTB column — amber tint
                    $sheet->getStyle("I4:I{$lastRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']],
                        'font' => ['color' => ['rgb' => '92400E']],
                    ]);
                    $sheet->getStyle('I4')->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '92400E']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FDE68A']],
                    ]);
                }

                // Colour movement columns (E = dep movement, H = loan movement)
                for ($row = 5; $row <= $lastRow; $row++) {
                    foreach (['E', 'H'] as $col) {
                        $v = $sheet->getCell("{$col}{$row}")->getValue();
                        if (!is_numeric($v)) continue;
                        $vf = (float) $v;
                        if ($vf > 0)     $sheet->getStyle("{$col}{$row}")->getFont()->getColor()->setRGB('0B6E4F');
                        elseif ($vf < 0) $sheet->getStyle("{$col}{$row}")->getFont()->getColor()->setRGB('B00020');
                    }
                }

                $sheet->freezePane('A5');
            },
        ];
    }
}
