<?php

declare(strict_types=1);

namespace App\Exports\Finance;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Daily RM Movers workbook: RM Summary (portfolio + deposit/loan movement, grouped by Job
 * Unit segment with subtotal rows) and, per RM (grouped in segment order), the top deposit
 * customer gainers/losers — same shape as BranchMoversWorkbookExport.
 */
class RmMoversWorkbookExport implements WithMultipleSheets
{
    /**
     * @param array<string, array{rmRows: \Illuminate\Support\Collection, totals: object, topGainers: \Illuminate\Support\Collection, topLosers: \Illuminate\Support\Collection}> $segmentsData
     *        keyed by segment name, in display order
     * @param object $grandTotals same shape as each segment's totals, summed across all segments
     * @param array $rmCodes RM codes in segment-grouped order (so the movers sheet groups by segment)
     * @param array<string,array{gainers: array, losers: array}> $groupedDrilldown from
     *        RmMoversService::drilldownGroupedByRmCodes(), keyed by rm_code
     */
    public function __construct(
        private readonly string $startDate,
        private readonly string $endDate,
        private readonly array $segmentsData,
        private readonly object $grandTotals,
        private readonly array $rmCodes,
        private readonly array $rmNames,
        private readonly array $groupedDrilldown
    ) {
    }

    public function sheets(): array
    {
        return [
            new RmSummarySheet($this->segmentsData, $this->grandTotals),
            new RmDepositMoversSheet($this->startDate, $this->endDate, $this->rmCodes, $this->rmNames, $this->groupedDrilldown),
        ];
    }
}

/**
 * SHEET 1: RM Summary — grouped by Job Unit segment, with a subtotal row per segment and
 * a grand Total row at the end.
 */
class RmSummarySheet implements FromArray, WithTitle, WithHeadings, ShouldAutoSize, WithStyles, WithColumnFormatting, WithEvents
{
    private array $boldRows = [];

    /** @param array<string, array{rmRows: \Illuminate\Support\Collection, totals: object}> $segmentsData */
    public function __construct(
        private readonly array $segmentsData,
        private readonly object $grandTotals
    ) {
    }

    public function title(): string
    {
        return 'RM Summary';
    }

    public function headings(): array
    {
        return [
            'Segment', 'RM Code', 'RM Name', 'Customers', 'Accounts',
            'Dep Start', 'Dep End', 'Dep Movement',
            'Loan Opening', 'Loan Closing', 'Loan Movement',
        ];
    }

    public function array(): array
    {
        if (empty($this->segmentsData)) {
            return [['No data', '', 'No qualifying movements for this period.', '', '', '', '', '', '', '', '']];
        }

        $rows = [];
        $rowNum = 1; // headings occupy row 1

        foreach ($this->segmentsData as $segment => $sd) {
            foreach ($sd['rmRows'] as $r) {
                $rows[] = [
                    $segment, (string) $r->rm_code, (string) $r->rm_name,
                    (int) $r->cif_count, (int) $r->total_accounts,
                    (float) $r->start_balance, (float) $r->end_balance, (float) $r->movement,
                    (float) $r->loan_open, (float) $r->loan_close, (float) $r->loan_movement,
                ];
                $rowNum++;
            }

            $t = $sd['totals'];
            $rows[] = [
                $segment . ' TOTAL', '', '',
                (int) $t->cif_count, (int) $t->total_accounts,
                (float) $t->start_balance, (float) $t->end_balance, (float) $t->movement,
                (float) $t->loan_open, (float) $t->loan_close, (float) $t->loan_movement,
            ];
            $rowNum++;
            $this->boldRows[] = $rowNum;
        }

        $g = $this->grandTotals;
        $rows[] = [
            'GRAND TOTAL', '', '',
            (int) $g->cif_count, (int) $g->total_accounts,
            (float) $g->start_balance, (float) $g->end_balance, (float) $g->movement,
            (float) $g->loan_open, (float) $g->loan_close, (float) $g->loan_movement,
        ];
        $rowNum++;
        $this->boldRows[] = $rowNum;

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER,
            'E' => NumberFormat::FORMAT_NUMBER,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet   = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                $sheet->getStyle('I1:K1')->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCFCE7']],
                    'font' => ['bold' => true, 'color' => ['rgb' => '166534']],
                ]);

                $sheet->getStyle("I1:I{$lastRow}")->applyFromArray([
                    'borders' => ['left' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'BBF7D0']]],
                ]);

                if ($lastRow > 1) {
                    $sheet->getStyle("I2:K{$lastRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
                        'font' => ['color' => ['rgb' => '166534']],
                    ]);
                }

                foreach ($this->boldRows as $r) {
                    $sheet->getStyle("A{$r}:K{$r}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                    ]);
                }
            },
        ];
    }
}
