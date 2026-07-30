<?php

declare(strict_types=1);

namespace App\Exports\Finance;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

class TopMoversSheetExport implements FromArray, WithTitle, WithEvents, WithColumnWidths
{
    // Scope constants — use these instead of raw strings at call sites
    public const SCOPE_CIF_ONLY     = 'CIF_ONLY';
    public const SCOPE_CIF_CURRENCY = 'CIF_CURRENCY';

    // Header block layout — single source of truth so styling never drifts
    private const META_ROWS   = 4; // rows 1-4: title + period + scope + generated
    private const SPACER_ROWS = 1; // row 5: blank
    private const HEADER_ROW  = self::META_ROWS + self::SPACER_ROWS + 1; // = 6

    /** Logical-name → Excel-letter map, built once from scope in constructor */
    private array $cols;

    public function __construct(
        private Collection $rows,
        private string $sheetName,
        private string $start,
        private string $end,
        private string $scope
    ) {
        $this->cols = $this->buildColumnMap();
    }

    public function title(): string
    {
        return $this->sheetName;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function isCifOnly(): bool
    {
        return $this->scope === self::SCOPE_CIF_ONLY;
    }

    /**
     * Build the column map once so every method references logical names,
     * not hardcoded letters. Adding/reordering a column means editing here only.
     */
    private function buildColumnMap(): array
    {
        return $this->isCifOnly()
            ? [
                'rank'          => 'A',
                'customer_name' => 'B',
                'cif'           => 'C',
                'branch'        => 'D',
                'start_balance' => 'E',
                'end_balance'   => 'F',
                'movement'      => 'G',
                'lcy_movement'  => 'H',
                'fcy_movement'  => 'I',
                'direction'     => 'J',
                'last'          => 'J',
            ]
            : [
                'rank'          => 'A',
                'customer_name' => 'B',
                'cif'           => 'C',
                'account_no'    => 'D',
                'branch'        => 'E',
                'currency'      => 'F',
                'start_balance' => 'G',
                'end_balance'   => 'H',
                'movement'      => 'I',
                'direction'     => 'J',
                'last'          => 'J',
            ];
    }

    // -------------------------------------------------------------------------
    // Data
    // -------------------------------------------------------------------------

    public function array(): array
    {
        $data = [];

        // Meta block (rows 1-4)
        $data[] = ['TOP MOVERS REPORT'];
        $data[] = ['Reporting Period', "{$this->start} → {$this->end}"];
        $data[] = ['Scope', $this->isCifOnly() ? 'CIF ONLY (KES Equivalent)' : 'CIF + Currency (LCY/FCY)'];
        $data[] = ['Generated', now()->timezone(config('app.timezone', 'Africa/Nairobi'))->format('Y-m-d H:i:s')];

        $data[] = array_fill(0, count($this->cols), null); // spacer (row 5)

        // Table header (row 6)
        $data[] = $this->isCifOnly()
            ? ['Rank', 'Customer Name', 'CIF', 'Branch', 'Start Balance (KES Eqv)', 'End Balance (KES Eqv)', 'Movement (KES Eqv)', 'LCY Movement', 'FCY Movement (KES Eqv)', 'Direction']
            : ['Rank', 'Customer Name', 'CIF', 'Account No', 'Branch', 'Currency', 'Start Balance', 'End Balance', 'Movement', 'Direction'];

        // Data rows
        foreach ($this->rows as $index => $r) {
            $rank = $index + 1;

            $data[] = $this->isCifOnly()
                ? [
                    $rank,
                    (string) ($r->customer_name ?? ''),
                    (string) ($r->cif           ?? ''),
                    (string) ($r->branch_code   ?? ''),
                    (float)  ($r->start_balance ?? 0),
                    (float)  ($r->end_balance   ?? 0),
                    (float)  ($r->movement      ?? 0),
                    (float)  ($r->lcy_movement  ?? 0),
                    (float)  ($r->fcy_movement  ?? 0),
                    (string) ($r->direction     ?? ''),
                ]
                : [
                    $rank,
                    (string) ($r->customer_name ?? ''),
                    (string) ($r->cif           ?? ''),
                    (string) ($r->cust_ac_no    ?? ''),
                    (string) ($r->branch_code   ?? ''),
                    (string) ($r->currency      ?? ''),
                    (float)  ($r->start_balance ?? 0),
                    (float)  ($r->end_balance   ?? 0),
                    (float)  ($r->movement      ?? 0),
                    (string) ($r->direction     ?? ''),
                ];
        }

        // Subtotal row for CIF_ONLY
        if ($this->isCifOnly() && $this->rows->isNotEmpty()) {
            $data[] = [
                '',
                'SUBTOTAL (' . $this->rows->count() . ' shown)',
                '', '',
                (float) $this->rows->sum('start_balance'),
                (float) $this->rows->sum('end_balance'),
                (float) $this->rows->sum('movement'),
                (float) $this->rows->sum('lcy_movement'),
                (float) $this->rows->sum('fcy_movement'),
                '',
            ];
        }

        return $data;
    }

    // -------------------------------------------------------------------------
    // Column widths
    // -------------------------------------------------------------------------

    /**
     * ShouldAutoSize has been removed — it conflicts with WithColumnWidths by
     * recalculating widths after the event, silently discarding values set here.
     */
    public function columnWidths(): array
    {
        return $this->isCifOnly()
            ? ['A' => 6, 'B' => 35, 'C' => 14, 'D' => 10, 'E' => 18, 'F' => 18, 'G' => 18, 'H' => 18, 'I' => 20, 'J' => 10]
            : ['A' => 6, 'B' => 35, 'C' => 14, 'D' => 16, 'E' => 10, 'F' => 10, 'G' => 18, 'H' => 18, 'I' => 18, 'J' => 10];
    }

    // -------------------------------------------------------------------------
    // Sheet events
    // -------------------------------------------------------------------------

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                /** @var Worksheet $sheet */
                $sheet     = $event->sheet->getDelegate();
                $lastCol   = $this->cols['last'];
                $headerRow = self::HEADER_ROW;
                $firstData = $headerRow + 1;

                // Snapshot lastRow once — used consistently throughout
                $lastRow = $sheet->getHighestRow();

                // --- Title row (row 1) ---
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);

                // --- Meta label column bold (A2:A4) ---
                $sheet->getStyle('A2:A4')->getFont()->setBold(true);

                // --- Table header row ---
                $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'size'  => 11,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1F3A5F'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Freeze pane below header
                $sheet->freezePane("A{$firstData}");

                // Auto-filter on header row
                $sheet->setAutoFilter("A{$headerRow}:{$lastCol}{$headerRow}");

                // --- Data-row styles ---
                if ($lastRow >= $firstData) {
                    $numStart    = $this->cols['start_balance'];
                    $movementCol = $this->cols['movement'];
                    $numEndCol   = isset($this->cols['fcy_movement']) ? $this->cols['fcy_movement'] : $movementCol;

                    // Number format + right-align: start_balance through last numeric column
                    $sheet->getStyle("{$numStart}{$firstData}:{$numEndCol}{$lastRow}")
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    $sheet->getStyle("{$numStart}{$firstData}:{$numEndCol}{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    // Colour movement, lcy_movement, fcy_movement by sign
                    $colourCols = array_filter([
                        $this->cols['movement']     ?? null,
                        $this->cols['lcy_movement'] ?? null,
                        $this->cols['fcy_movement'] ?? null,
                    ]);

                    for ($row = $firstData; $row <= $lastRow; $row++) {
                        foreach ($colourCols as $col) {
                            $v = $sheet->getCell("{$col}{$row}")->getValue();
                            if (!is_numeric($v)) continue;
                            $vf = (float) $v;
                            if ($vf > 0)      $sheet->getStyle("{$col}{$row}")->getFont()->getColor()->setRGB('0B6E4F');
                            elseif ($vf < 0)  $sheet->getStyle("{$col}{$row}")->getFont()->getColor()->setRGB('B00020');
                        }
                    }

                    // Subtotal row: bold + light gray background
                    if ($this->isCifOnly() && $this->rows->isNotEmpty()) {
                        $subtotalRow = $lastRow;
                        $sheet->getStyle("A{$subtotalRow}:{$lastCol}{$subtotalRow}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 11],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8EDF2']],
                        ]);
                        $sheet->getStyle("A{$subtotalRow}:B{$subtotalRow}")->getFont()->getColor()->setRGB('1F3A5F');
                    }

                    // Distinct header tint for LCY/FCY columns
                    if (isset($this->cols['lcy_movement'], $this->cols['fcy_movement'])) {
                        $lcyCol = $this->cols['lcy_movement'];
                        $fcyCol = $this->cols['fcy_movement'];
                        $sheet->getStyle("{$lcyCol}{$headerRow}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
                        ]);
                        $sheet->getStyle("{$fcyCol}{$headerRow}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '92400E']],
                        ]);
                    }

                    $sheet->getStyle("A{$headerRow}:{$lastCol}{$lastRow}")
                        ->getAlignment()
                        ->setVertical(Alignment::VERTICAL_CENTER);
                }

                // Row heights
                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getRowDimension($headerRow)->setRowHeight(18);
            },
        ];
    }
}
