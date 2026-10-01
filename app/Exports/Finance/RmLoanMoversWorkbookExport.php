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
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Daily RM Loan Movers workbook: RM Loan Summary (grouped by Job Unit segment with subtotal
 * rows) and, per RM (grouped in segment order), the top loan account gainers/losers — same
 * shape as RmMoversWorkbookExport (the deposit equivalent), scoped to the performing,
 * non-Corporate, non-staff loan book.
 */
class RmLoanMoversWorkbookExport implements WithMultipleSheets
{
    /**
     * @param array<string, array{rmRows: \Illuminate\Support\Collection, totals: object, topGainers: \Illuminate\Support\Collection, topLosers: \Illuminate\Support\Collection}> $segmentsData
     *        keyed by segment name, in display order
     * @param object $grandTotals same shape as each segment's totals, summed across all segments
     * @param array $rmCodes RM codes in segment-grouped order (so the movers sheet groups by segment)
     * @param array<string,array{gainers: array, losers: array}> $groupedDrilldown from
     *        RmLoanMoversService::accountMoversGroupedByRmCodes(), keyed by rm_code
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
            new RmLoanSummarySheet($this->segmentsData, $this->grandTotals),
            new LoanAccountMoversByRmSheet($this->startDate, $this->endDate, $this->rmCodes, $this->rmNames, $this->groupedDrilldown),
        ];
    }
}

/**
 * SHEET 1: RM Loan Summary — grouped by Job Unit segment, with a subtotal row per segment
 * and a grand Total row at the end.
 */
class RmLoanSummarySheet implements FromArray, WithTitle, WithHeadings, ShouldAutoSize, WithStyles, WithColumnFormatting, WithEvents
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
        return 'RM Loan Summary';
    }

    public function headings(): array
    {
        return ['Segment', 'RM Code', 'RM Name', 'Loan Accounts', 'Customers', 'Loan Open', 'Loan Close', 'Loan Movement'];
    }

    public function array(): array
    {
        if (empty($this->segmentsData)) {
            return [['No data', '', 'No qualifying movements for this period.', '', '', '', '', '']];
        }

        $rows = [];
        $rowNum = 1;

        foreach ($this->segmentsData as $segment => $sd) {
            foreach ($sd['rmRows'] as $r) {
                $rows[] = [
                    $segment, (string) $r->rm_code, (string) $r->rm_name,
                    (int) $r->account_count, (int) $r->customer_count,
                    (float) $r->loan_open, (float) $r->loan_close, (float) $r->loan_movement,
                ];
                $rowNum++;
            }

            $t = $sd['totals'];
            $rows[] = [
                $segment . ' TOTAL', '', '',
                (int) $t->account_count, (int) $t->customer_count,
                (float) $t->loan_open, (float) $t->loan_close, (float) $t->loan_movement,
            ];
            $rowNum++;
            $this->boldRows[] = $rowNum;
        }

        $g = $this->grandTotals;
        $rows[] = [
            'GRAND TOTAL', '', '',
            (int) $g->account_count, (int) $g->customer_count,
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
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                foreach ($this->boldRows as $r) {
                    $sheet->getStyle("A{$r}:H{$r}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                    ]);
                }
            },
        ];
    }
}
