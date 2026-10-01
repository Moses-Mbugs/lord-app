<?php

declare(strict_types=1);

namespace App\Exports\Finance;

use Illuminate\Support\Collection;
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
 * Daily RM Loan Movers workbook: RM Loan Summary (accounts/customers + open/close/movement)
 * and, per RM, the top loan account gainers/losers — same shape as RmMoversWorkbookExport
 * (the deposit equivalent), scoped to the performing, non-Corporate, non-staff loan book.
 */
class RmLoanMoversWorkbookExport implements WithMultipleSheets
{
    /**
     * @param Collection $rmRows rows built by EmailRmLoanMoversCommand (rm_code, rm_name,
     *        account_count, customer_count, loan_open, loan_close, loan_movement)
     * @param object $totals same shape, summed across $rmRows
     * @param array<string,array{gainers: array, losers: array}> $groupedDrilldown from
     *        RmLoanMoversService::accountMoversGroupedByRmCodes(), keyed by rm_code
     */
    public function __construct(
        private readonly string $startDate,
        private readonly string $endDate,
        private readonly Collection $rmRows,
        private readonly object $totals,
        private readonly array $rmCodes,
        private readonly array $rmNames,
        private readonly array $groupedDrilldown
    ) {
    }

    public function sheets(): array
    {
        return [
            new RmLoanSummarySheet($this->rmRows, $this->totals),
            new LoanAccountMoversByRmSheet($this->startDate, $this->endDate, $this->rmCodes, $this->rmNames, $this->groupedDrilldown),
        ];
    }
}

/**
 * SHEET 1: RM Loan Summary — one row per RM plus a Total row.
 */
class RmLoanSummarySheet implements FromArray, WithTitle, WithHeadings, ShouldAutoSize, WithStyles, WithColumnFormatting, WithEvents
{
    public function __construct(
        private readonly Collection $rmRows,
        private readonly object $totals
    ) {
    }

    public function title(): string
    {
        return 'RM Loan Summary';
    }

    public function headings(): array
    {
        return ['RM Code', 'RM Name', 'Loan Accounts', 'Customers', 'Loan Open', 'Loan Close', 'Loan Movement'];
    }

    public function array(): array
    {
        if ($this->rmRows->isEmpty()) {
            return [['No data', 'No qualifying movements for this period.', '', '', '', '', '']];
        }

        $rows = $this->rmRows->map(fn ($r) => [
            (string) $r->rm_code,
            (string) $r->rm_name,
            (int) $r->account_count,
            (int) $r->customer_count,
            (float) $r->loan_open,
            (float) $r->loan_close,
            (float) $r->loan_movement,
        ])->toArray();

        $rows[] = [
            'TOTAL', '',
            (int) $this->totals->account_count,
            (int) $this->totals->customer_count,
            (float) $this->totals->loan_open,
            (float) $this->totals->loan_close,
            (float) $this->totals->loan_movement,
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER,
            'D' => NumberFormat::FORMAT_NUMBER,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet   = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                if ($lastRow > 1) {
                    $sheet->getStyle("A{$lastRow}:G{$lastRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                    ]);
                }
            },
        ];
    }
}
