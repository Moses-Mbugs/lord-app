<?php

declare(strict_types=1);

namespace App\Exports\Finance;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * DSA account opening workbook: Summary (WTD/MTD), Month on Month, Week on Week, and the
 * account-level detail behind them. $report is DsaAccountOpeningService::build().
 */
class DsaAccountOpeningWorkbookExport implements WithMultipleSheets
{
    public function __construct(private readonly array $report)
    {
    }

    public function sheets(): array
    {
        $r = $this->report;

        $summaryRow = fn ($d) => [$d->dsa_code, $d->wtd_accounts, $d->wtd_customers, $d->mtd_accounts, $d->mtd_customers];
        $monthRow   = fn ($d) => [$d->dsa_code, ...array_values($d->months), array_sum($d->months)];
        $weekRow    = fn ($d) => [$d->dsa_code, ...array_values($d->weeks), array_sum($d->weeks)];

        return [
            new DsaMatrixSheet(
                'Summary',
                ['DSA Code', 'WTD Accounts', 'WTD Customers', 'MTD Accounts', 'MTD Customers'],
                $r['dsaRows']->map($summaryRow)->all(),
                $summaryRow($r['totals'])
            ),
            new DsaMatrixSheet(
                'Month on Month',
                ['DSA Code', ...array_column($r['months'], 'label'), 'Total'],
                $r['dsaRows']->map($monthRow)->all(),
                $monthRow($r['totals'])
            ),
            new DsaMatrixSheet(
                'Week on Week',
                ['DSA Code', ...array_column($r['weeks'], 'label'), 'Total'],
                $r['dsaRows']->map($weekRow)->all(),
                $weekRow($r['totals'])
            ),
            new DsaAccountDetailSheet($r['accounts']),
        ];
    }
}

/** A DSA-per-row count table with a bold TOTAL row at the bottom. */
class DsaMatrixSheet implements FromArray, WithTitle, WithHeadings, ShouldAutoSize, WithEvents
{
    public function __construct(
        private readonly string $title,
        private readonly array $headings,
        private readonly array $rows,
        private readonly array $totalRow
    ) {
    }

    public function title(): string
    {
        return $this->title;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        if (empty($this->rows)) {
            return [['No DSA-introduced accounts opened in this period.']];
        }

        return [...$this->rows, $this->totalRow];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();

                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray(DsaSheetStyle::HEADER);
                $sheet->freezePane('B2');

                if (! empty($this->rows)) {
                    $sheet->getStyle("A{$lastRow}:{$lastCol}{$lastRow}")->applyFromArray(DsaSheetStyle::TOTAL);
                }
            },
        ];
    }
}

/** Every DSA-introduced account in the report window; CIF and account no kept as text. */
class DsaAccountDetailSheet extends DefaultValueBinder implements FromArray, WithTitle, WithHeadings, ShouldAutoSize, WithEvents, WithCustomValueBinder
{
    public function __construct(private readonly Collection $accounts)
    {
    }

    public function title(): string
    {
        return 'Account Detail';
    }

    public function headings(): array
    {
        return ['DSA Code', 'CIF', 'Account No', 'Customer Name', 'Branch', 'Open Date', 'Week Commencing', 'Month'];
    }

    public function array(): array
    {
        return $this->accounts
            ->sortBy([['open_date', 'desc'], ['dsa_code', 'asc']])
            ->map(fn ($a) => [
                $a->dsa_code, $a->cif, $a->account_no, $a->customer_name, $a->branch_code,
                $a->open_date, $a->week_key, $a->month_key,
            ])
            ->values()
            ->all();
    }

    public function bindValue(Cell $cell, $value): bool
    {
        // Long CIF/account numbers would otherwise become floats in scientific notation.
        if (in_array($cell->getColumn(), ['B', 'C'], true) && $cell->getRow() > 1) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A1:H1')->applyFromArray(DsaSheetStyle::HEADER);
                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:H' . max(1, $sheet->getHighestRow()));
            },
        ];
    }
}

final class DsaSheetStyle
{
    public const HEADER = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '005B82']],
    ];

    public const TOTAL = [
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
    ];
}
