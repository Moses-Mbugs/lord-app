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
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Daily RM Movers workbook: RM Summary (portfolio + deposit/loan movement) and, per RM,
 * the top deposit customer gainers/losers — same shape as BranchMoversWorkbookExport.
 */
class RmMoversWorkbookExport implements WithMultipleSheets
{
    /**
     * @param Collection $rmRows rows built by EmailRmMoversCommand (rm_code, rm_name,
     *        start_balance, end_balance, movement, cif_count, total_accounts, loan_open,
     *        loan_close, loan_movement)
     * @param object $totals same shape, summed across $rmRows
     * @param array<string,array{gainers: array, losers: array}> $groupedDrilldown from
     *        RmMoversService::drilldownGroupedByRmCodes(), keyed by rm_code
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
            new RmSummarySheet($this->rmRows, $this->totals),
            new RmDepositMoversSheet($this->startDate, $this->endDate, $this->rmCodes, $this->rmNames, $this->groupedDrilldown),
        ];
    }
}

/**
 * SHEET 1: RM Summary — one row per RM plus a Total row.
 */
class RmSummarySheet implements FromArray, WithTitle, WithHeadings, ShouldAutoSize, WithStyles, WithColumnFormatting, WithEvents
{
    public function __construct(
        private readonly Collection $rmRows,
        private readonly object $totals
    ) {
    }

    public function title(): string
    {
        return 'RM Summary';
    }

    public function headings(): array
    {
        return [
            'RM Code', 'RM Name', 'Customers', 'Accounts',
            'Dep Start', 'Dep End', 'Dep Movement',
            'Loan Opening', 'Loan Closing', 'Loan Movement',
        ];
    }

    public function array(): array
    {
        if ($this->rmRows->isEmpty()) {
            return [['No data', 'No qualifying movements for this period.', '', '', '', '', '', '', '', '']];
        }

        $rows = $this->rmRows->map(fn ($r) => [
            (string) $r->rm_code,
            (string) $r->rm_name,
            (int) $r->cif_count,
            (int) $r->total_accounts,
            (float) $r->start_balance,
            (float) $r->end_balance,
            (float) $r->movement,
            (float) $r->loan_open,
            (float) $r->loan_close,
            (float) $r->loan_movement,
        ])->toArray();

        $rows[] = [
            'TOTAL', '',
            (int) $this->totals->cif_count,
            (int) $this->totals->total_accounts,
            (float) $this->totals->start_balance,
            (float) $this->totals->end_balance,
            (float) $this->totals->movement,
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
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet   = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                $sheet->getStyle('H1:J1')->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCFCE7']],
                    'font' => ['bold' => true, 'color' => ['rgb' => '166534']],
                ]);

                $sheet->getStyle("H1:H{$lastRow}")->applyFromArray([
                    'borders' => ['left' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'BBF7D0']]],
                ]);

                if ($lastRow > 1) {
                    $sheet->getStyle("H2:J{$lastRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
                        'font' => ['color' => ['rgb' => '166534']],
                    ]);

                    $sheet->getStyle("A{$lastRow}:J{$lastRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                    ]);
                }
            },
        ];
    }
}
