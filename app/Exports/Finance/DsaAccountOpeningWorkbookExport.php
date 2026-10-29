<?php

declare(strict_types=1);

namespace App\Exports\Finance;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/**
 * DSA account opening workbook: Summary (book + WTD/MTD), Month on Month, Week on Week, and
 * the account-level detail behind them. $report is DsaAccountOpeningService::build().
 */
class DsaAccountOpeningWorkbookExport implements WithMultipleSheets
{
    private const COUNT = '#,##0';
    private const MONEY = '#,##0';
    private const DATE  = 'dd-mmm-yy';

    public function __construct(private readonly array $report)
    {
    }

    public function sheets(): array
    {
        $r = $this->report;
        $t = $r['totals'];

        $daily  = $r['newStart'] !== null;
        $prefix = $daily ? 'DSA Daily Report' : 'DSA Weekly Report';

        $fmt      = fn (?string $d) => $d ? Carbon::parse($d)->format('d M Y') : 'n/a';
        $subtitle = "As at {$fmt($r['asOf'])}  ·  WTD from {$fmt($r['wtdStart'])}  ·  MTD from {$fmt($r['mtdStart'])}"
            . "  ·  Deposits as at {$fmt($r['depositDate'])}  ·  Loans as at {$fmt($r['loanDate'])}  ·  Staff accounts excluded";
        $newRange = $daily ? "New = opened {$fmt($r['newStart'])} – {$fmt($r['asOf'])}" : '';

        $codeCol  = ['width' => 12, 'text' => true];
        $nameCol  = ['width' => 30];
        $countCol = ['width' => 13, 'format' => self::COUNT];
        $moneyCol = ['width' => 18, 'format' => self::MONEY];

        $summaryRow = fn ($d) => [
            $d->dsa_code, $d->dsa_name, ...($daily ? [$d->new_accounts] : []), $d->book_accounts, $d->book_customers,
            $d->wtd_accounts, $d->wtd_customers, $d->mtd_accounts, $d->mtd_customers,
            $d->deposits, $d->loans,
        ];
        $monthRow = fn ($d) => [$d->dsa_code, $d->dsa_name, ...array_values($d->months), array_sum($d->months)];
        $weekRow  = fn ($d) => [$d->dsa_code, $d->dsa_name, ...array_values($d->weeks), array_sum($d->weeks)];

        return [
            ...($daily ? [$this->accountSheet(
                $r['newAccounts'],
                'New Accounts',
                "{$prefix} — New Accounts",
                $newRange . '  ·  ' . $subtitle . '  ·  Customer loans shown once per CIF',
                'No DSA-introduced accounts were opened in this period.'
            )] : []),
            new DsaReportSheet(
                'Summary',
                "{$prefix} — Summary",
                ($daily ? $newRange . '  ·  ' : '') . $subtitle . '  ·  Deposits/Loans cover every account the DSA has opened; loans are by customer (CIF)',
                [
                    'DSA Code', 'DSA Name', ...($daily ? ['New Accounts'] : []), 'Accounts Opened (All Time)', 'Customers (All Time)',
                    'WTD Accounts', 'WTD Customers', 'MTD Accounts', 'MTD Customers',
                    'Deposits (KES)', 'Loans (KES)',
                ],
                [
                    $codeCol, $nameCol, ...($daily ? [$countCol] : []), $countCol + ['width' => 15], $countCol + ['width' => 15],
                    $countCol, $countCol, $countCol, $countCol, $moneyCol, $moneyCol,
                ],
                $r['dsaRows']->map($summaryRow)->all(),
                $summaryRow($t)
            ),
            new DsaReportSheet(
                'Month on Month',
                "{$prefix} — Month on Month",
                $subtitle,
                ['DSA Code', 'DSA Name', ...array_column($r['months'], 'label'), 'Total'],
                [$codeCol, $nameCol, ...array_fill(0, count($r['months']) + 1, $countCol + ['width' => 16])],
                $r['dsaRows']->map($monthRow)->all(),
                $monthRow($t)
            ),
            new DsaReportSheet(
                'Week on Week',
                "{$prefix} — Week on Week",
                $subtitle . '  ·  Mon–Sun weeks',
                ['DSA Code', 'DSA Name', ...array_column($r['weeks'], 'label'), 'Total'],
                [$codeCol, $nameCol, ...array_fill(0, count($r['weeks']) + 1, $countCol + ['width' => 15])],
                $r['dsaRows']->map($weekRow)->all(),
                $weekRow($t)
            ),
            $this->accountSheet(
                $r['accounts'],
                'Account Detail',
                "{$prefix} — Account Detail (all DSA accounts)",
                $subtitle . '  ·  Customer loans shown once per CIF'
            ),
        ];
    }

    /** Account-level list (newest first) with a TOTAL row computed from the accounts given. */
    private function accountSheet(Collection $accounts, string $sheetTitle, string $heading, string $subtitle, string $emptyMessage = 'No DSA-introduced accounts found.'): DsaReportSheet
    {
        $excelDate = fn (?string $d) => $d ? ExcelDate::stringToExcel($d) : '';

        // Loans are per customer: show them on the CIF's first row only, so the column sums correctly.
        $seenCif = [];
        $loanTotal = 0.0;
        $rows = $accounts
            ->sortBy([fn ($a, $b) => strcmp($b->open_date ?? '', $a->open_date ?? '') ?: strcmp($a->dsa_code, $b->dsa_code)])
            ->map(function ($a) use ($excelDate, &$seenCif, &$loanTotal) {
                $firstForCif = $a->cif !== '' && ! isset($seenCif[$a->cif]);
                $seenCif[$a->cif] = true;
                $loanTotal += $firstForCif ? $a->cif_loans : 0;

                return [
                    $a->dsa_code, $a->dsa_name, $a->cif, $a->account_no, $a->customer_name, $a->branch_code,
                    $excelDate($a->open_date), $excelDate($a->week_key),
                    $a->month_key ? Carbon::parse($a->month_key . '-01')->format('M Y') : '',
                    $a->deposit_balance,
                    $firstForCif ? $a->cif_loans : '',
                ];
            })
            ->values()
            ->all();

        $customers = $accounts->pluck('cif')->filter()->unique()->count();

        return new DsaReportSheet(
            $sheetTitle,
            $heading,
            $subtitle,
            [
                'DSA Code', 'DSA Name', 'CIF', 'Account No', 'Customer Name', 'Branch',
                'Open Date', 'Week Commencing', 'Month', 'Deposit Balance (KES)', 'Customer Loans (KES)',
            ],
            [
                ['width' => 12, 'text' => true],
                ['width' => 28],
                ['width' => 13, 'text' => true],
                ['width' => 17, 'text' => true],
                ['width' => 38],
                ['width' => 9, 'align' => Alignment::HORIZONTAL_CENTER],
                ['width' => 12, 'format' => self::DATE, 'align' => Alignment::HORIZONTAL_CENTER],
                ['width' => 12, 'format' => self::DATE, 'align' => Alignment::HORIZONTAL_CENTER],
                ['width' => 10, 'align' => Alignment::HORIZONTAL_CENTER],
                ['width' => 20, 'format' => self::MONEY],
                ['width' => 20, 'format' => self::MONEY],
            ],
            $rows,
            [
                'TOTAL', '', '', '', number_format($accounts->count()) . ' accounts · ' . number_format($customers) . ' customers',
                '', '', '', '', round((float) $accounts->sum('deposit_balance'), 2), round($loanTotal, 2),
            ],
            'E5',
            true,
            $emptyMessage
        );
    }
}

/**
 * One styled report sheet: a title banner (row 1), subtitle (row 2), headings (row 4), data
 * rows with zebra striping, and an optional bold TOTAL row.
 */
class DsaReportSheet extends DefaultValueBinder implements FromArray, WithTitle, WithEvents, WithCustomValueBinder
{
    private const HEADER_ROW = 4;
    private const BRAND      = '005B82';

    /**
     * @param array<int, array{width: int, format?: string, text?: bool, align?: string}> $columns
     *        one spec per heading; 'text' keeps long numbers (CIF, account no) as text
     */
    public function __construct(
        private readonly string $sheetTitle,
        private readonly string $heading,
        private readonly string $subtitle,
        private readonly array $headings,
        private readonly array $columns,
        private readonly array $rows,
        private readonly ?array $totalRow = null,
        private readonly string $freezeAt = 'C5',
        private readonly bool $autoFilter = false,
        private readonly string $emptyMessage = 'No DSA-introduced accounts found.'
    ) {
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function array(): array
    {
        $body = empty($this->rows)
            ? [[$this->emptyMessage]]
            : [...$this->rows, ...($this->totalRow ? [$this->totalRow] : [])];

        return [[$this->heading], [$this->subtitle], [''], $this->headings, ...$body];
    }

    public function bindValue(Cell $cell, $value): bool
    {
        $spec = $this->columns[Coordinate::columnIndexFromString($cell->getColumn()) - 1] ?? [];

        if ($cell->getRow() > self::HEADER_ROW && ($spec['text'] ?? false) && $value !== null && $value !== '') {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet     = $event->sheet->getDelegate();
                $h         = self::HEADER_ROW;
                $last      = Coordinate::stringFromColumnIndex(count($this->headings));
                $firstData = $h + 1;
                $lastRow   = $sheet->getHighestRow();
                $hasTotal  = ! empty($this->rows) && $this->totalRow !== null;
                $dataEnd   = $hasTotal ? $lastRow - 1 : $lastRow;

                $sheet->setShowGridlines(false);

                // Title banner + subtitle
                $sheet->mergeCells("A1:{$last}1");
                $sheet->mergeCells("A2:{$last}2");
                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getStyle("A1:{$last}1")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::BRAND]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(18);
                $sheet->getStyle("A2:{$last}2")->applyFromArray([
                    'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '646464']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
                ]);

                // Column widths, number formats, alignment
                foreach ($this->columns as $i => $spec) {
                    $col = Coordinate::stringFromColumnIndex($i + 1);
                    $sheet->getColumnDimension($col)->setWidth($spec['width']);

                    if ($lastRow < $firstData) {
                        continue;
                    }

                    $range = "{$col}{$firstData}:{$col}{$lastRow}";
                    if (isset($spec['format'])) {
                        $sheet->getStyle($range)->getNumberFormat()->setFormatCode($spec['format']);
                    }
                    $sheet->getStyle($range)->getAlignment()->setHorizontal(
                        $spec['align'] ?? (isset($spec['format']) ? Alignment::HORIZONTAL_RIGHT : Alignment::HORIZONTAL_LEFT)
                    );
                }

                // Header row
                $sheet->getRowDimension($h)->setRowHeight(32);
                $sheet->getStyle("A{$h}:{$last}{$h}")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::BRAND]],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                ]);

                if (empty($this->rows)) {
                    $sheet->mergeCells("A{$firstData}:{$last}{$firstData}");
                    $sheet->getStyle("A{$firstData}")->getFont()->setItalic(true)->getColor()->setRGB('646464');
                    return;
                }

                // Body: light borders + zebra striping
                $sheet->getStyle("A{$h}:{$last}{$lastRow}")->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E0E0E0');

                for ($row = $firstData; $row <= $dataEnd; $row++) {
                    if (($row - $firstData) % 2 === 1) {
                        $sheet->getStyle("A{$row}:{$last}{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F5F8FA');
                    }
                }

                // DSA code column in brand colour
                $sheet->getStyle("A{$firstData}:A{$dataEnd}")->getFont()->setBold(true)->getColor()->setRGB(self::BRAND);

                if ($hasTotal) {
                    $sheet->getRowDimension($lastRow)->setRowHeight(20);
                    $sheet->getStyle("A{$lastRow}:{$last}{$lastRow}")->applyFromArray([
                        'font'    => ['bold' => true],
                        'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                        'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::BRAND]]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                }

                $sheet->freezePane($this->freezeAt);

                if ($this->autoFilter) {
                    // Total row kept out of the filter range so sorting doesn't move it.
                    $sheet->setAutoFilter("A{$h}:{$last}{$dataEnd}");
                }

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0)
                    ->setRowsToRepeatAtTopByStartAndEnd($h, $h);
            },
        ];
    }
}
