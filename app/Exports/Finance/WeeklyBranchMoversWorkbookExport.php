<?php

declare(strict_types=1);

namespace App\Exports\Finance;

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
        private readonly array  $period,
        private readonly array  $data,
        private readonly int    $limit = 10
    ) {}

    public function sheets(): array
    {
        return [
            new WeeklyBranchPeriodSheet('Weekly', $this->period, $this->data, $this->limit),
        ];
    }
}

/**
 * SHEET: Weekly branch summary (Deposits Δ, Loans Δ, NTB) + top gainers/losers.
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
