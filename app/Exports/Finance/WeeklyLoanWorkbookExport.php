<?php

declare(strict_types=1);

namespace App\Exports\Finance;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WeeklyLoanWorkbookExport implements WithMultipleSheets
{
    public function __construct(
        private readonly array $data,
        private readonly array $drilldown,
        private readonly array $weekTopMovers = ['gainers' => [], 'losers' => []],
        private readonly array $mtdTopMovers = ['gainers' => [], 'losers' => []]
    ) {}

    public function sheets(): array
    {
        return [
            new WeeklyLoanSummarySheet($this->data),
            new WeeklyLoanTopMoversSheet($this->weekTopMovers, $this->mtdTopMovers, $this->data['periods'] ?? []),
            new WeeklyLoanDrilldownSheet($this->drilldown, $this->data['periods'] ?? []),
        ];
    }
}

// =============================================================================
// SHEET — Top 10 Gainers / Losers (overall, Week + MTD)
// =============================================================================

class WeeklyLoanTopMoversSheet implements FromArray, WithTitle, WithColumnWidths, WithEvents
{
    private array $mergeRows      = [];
    private array $sectionHdrRows = [];
    private array $gainHdrRows    = [];
    private array $lossHdrRows    = [];
    private array $gainDataRows   = [];
    private array $lossDataRows   = [];

    public function __construct(
        private readonly array $weekTopMovers,
        private readonly array $mtdTopMovers,
        private readonly array $periods
    ) {}

    public function title(): string { return 'Top 10 Movers'; }

    public function columnWidths(): array
    {
        return ['A' => 6, 'B' => 16, 'C' => 34, 'D' => 10, 'E' => 18, 'F' => 18, 'G' => 18];
    }

    public function array(): array
    {
        $weekStart = $this->periods['week_start'] ?? '';
        $weekEnd   = $this->periods['week_end']   ?? '';
        $mtdStart  = $this->periods['mtd_start']  ?? '';

        $rows   = [];
        $rowNum = 0;

        $rows[] = ['PERFORMING LOANS — TOP 10 GAINERS & LOSERS', '', '', '', '', '', ''];
        $this->mergeRows[] = ++$rowNum;

        $rows[] = ['', '', '', '', '', '', ''];
        $rowNum++;

        $rowNum = $this->appendPeriodSection(
            $rows,
            $rowNum,
            "WEEK  ({$weekStart}  →  {$weekEnd})",
            $this->weekTopMovers
        );

        $rows[] = ['', '', '', '', '', '', ''];
        $rowNum++;

        $this->appendPeriodSection(
            $rows,
            $rowNum,
            "MTD  ({$mtdStart}  →  {$weekEnd})",
            $this->mtdTopMovers
        );

        return $rows;
    }

    private function appendPeriodSection(array &$rows, int $rowNum, string $label, array $topMovers): int
    {
        $gainers = collect($topMovers['gainers'] ?? []);
        $losers  = collect($topMovers['losers']  ?? []);

        $rows[] = [$label, '', '', '', '', '', ''];
        $this->mergeRows[]      = ++$rowNum;
        $this->sectionHdrRows[] = $rowNum;

        if ($gainers->isNotEmpty()) {
            $rows[] = ['▲  TOP GAINERS', '', '', '', '', '', ''];
            $this->mergeRows[]   = ++$rowNum;
            $this->gainHdrRows[] = $rowNum;

            $rows[] = ['#', 'CIF', 'Customer Name', 'Branch', 'Start Balance', 'End Balance', 'Movement'];
            $this->gainHdrRows[] = ++$rowNum;

            foreach ($gainers as $i => $r) {
                $rows[] = [
                    $i + 1,
                    (string) ($r->cif           ?? ''),
                    (string) ($r->customer_name ?? ''),
                    (string) ($r->branch_code   ?? ''),
                    (float)  ($r->start_balance ?? 0),
                    (float)  ($r->end_balance   ?? 0),
                    (float)  ($r->movement      ?? 0),
                ];
                $this->gainDataRows[] = ++$rowNum;
            }
        }

        if ($losers->isNotEmpty()) {
            $rows[] = ['▼  TOP LOSERS', '', '', '', '', '', ''];
            $this->mergeRows[]   = ++$rowNum;
            $this->lossHdrRows[] = $rowNum;

            $rows[] = ['#', 'CIF', 'Customer Name', 'Branch', 'Start Balance', 'End Balance', 'Movement'];
            $this->lossHdrRows[] = ++$rowNum;

            foreach ($losers as $i => $r) {
                $rows[] = [
                    $i + 1,
                    (string) ($r->cif           ?? ''),
                    (string) ($r->customer_name ?? ''),
                    (string) ($r->branch_code   ?? ''),
                    (float)  ($r->start_balance ?? 0),
                    (float)  ($r->end_balance   ?? 0),
                    (float)  ($r->movement      ?? 0),
                ];
                $this->lossDataRows[] = ++$rowNum;
            }
        }

        return $rowNum;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                foreach ($this->mergeRows as $r) {
                    $sheet->mergeCells("A{$r}:G{$r}");
                }

                $sheet->getStyle('A1:G1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3A5F']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(24);

                foreach ($this->sectionHdrRows as $r) {
                    $sheet->getStyle("A{$r}:G{$r}")->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getRowDimension($r)->setRowHeight(18);
                }

                foreach ($this->gainHdrRows as $r) {
                    $sheet->getStyle("A{$r}:G{$r}")->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '166534']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }

                foreach ($this->lossHdrRows as $r) {
                    $sheet->getStyle("A{$r}:G{$r}")->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '991B1B']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }

                foreach ($this->gainDataRows as $r) {
                    $sheet->getStyle("A{$r}:G{$r}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
                    ]);
                    $this->styleDataRow($sheet, $r, true);
                }

                foreach ($this->lossDataRows as $r) {
                    $sheet->getStyle("A{$r}:G{$r}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF1F2']],
                    ]);
                    $this->styleDataRow($sheet, $r, false);
                }

                $sheet->freezePane('A3');
            },
        ];
    }

    private function styleDataRow(Worksheet $sheet, int $r, bool $isGain): void
    {
        $sheet->getStyle("E{$r}:G{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("E{$r}:G{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("G{$r}")->getFont()->getColor()->setRGB($isGain ? '166534' : '991B1B');
        $sheet->getStyle("A{$r}:G{$r}")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('E2E8F0');
    }
}

// =============================================================================
// SHEET 1 — Loan Summary (KES equivalent, LCY+FCY combined)
// =============================================================================

class WeeklyLoanSummarySheet implements FromArray, WithTitle, WithColumnWidths, WithEvents
{
    private array $segRowCodes = [];
    private array $subRowCodes = [];
    private array $totalRows   = [];
    private int   $headerRow   = 0;
    private int   $titleRow    = 0;

    private const SEG_FILL = [
        'CORPORATE BANKING'  => ['row' => 'DBEAFE', 'sub' => 'EFF6FF'],  // blue
        'COMMERCIAL BANKING' => ['row' => 'EDE9FE', 'sub' => 'F5F3FF'],  // purple
        'CONSUMER BANKING'   => ['row' => 'FEF3C7', 'sub' => 'FFFBEB'],  // amber
        'UNMAPPED'           => ['row' => 'F1F5F9', 'sub' => 'F8FAFC'],  // gray
        'ALL'                => ['row' => 'E2E8F0', 'sub' => 'E2E8F0'],  // darker gray
    ];

    public function __construct(private readonly array $data) {}

    public function title(): string { return 'Performing Loan Summary'; }

    public function columnWidths(): array
    {
        return ['A' => 40, 'B' => 20, 'C' => 20, 'D' => 24];
    }

    public function array(): array
    {
        $periods = $this->data['periods'] ?? [];
        $rows    = [];
        $rowNum  = 0;

        $this->titleRow = ++$rowNum;
        $rows[] = ['WEEKLY PERFORMING LOAN MOVEMENT REPORT', '', '', ''];

        $rows[] = ['', '', '', ''];
        $rowNum++;

        $rows[] = ['Week',    ($periods['week_start'] ?? '') . '  →  ' . ($periods['week_end'] ?? ''), '', ''];
        $rowNum++;

        $rows[] = ['MTD from', $periods['mtd_start'] ?? '', '', ''];
        $rowNum++;

        $rows[] = ['Generated', now()->timezone(config('app.timezone', 'Africa/Nairobi'))->format('d M Y  H:i'), '', ''];
        $rowNum++;

        $rows[] = ['', '', '', ''];
        $rowNum++;

        $this->headerRow = ++$rowNum;
        $rows[] = [
            'Segment / Sub-Segment',
            'Weekly Movement',
            'MTD Movement',
            'Total Performing Loans (closing)',
        ];

        $segments = $this->data['segments'] ?? [];

        foreach ($segments as $seg) {
            $code    = $seg['code'] ?? 'UNMAPPED';
            $isTotal = ($code === 'ALL');
            $rowNo   = ++$rowNum;

            $rows[] = [
                $seg['name'] ?? $code,
                (float) ($seg['weekly_mv']   ?? 0),
                (float) ($seg['mtd_mv']      ?? 0),
                (float) ($seg['total_loans'] ?? 0),
            ];

            if ($isTotal) {
                $this->totalRows[] = $rowNo;
            } else {
                $this->segRowCodes[$rowNo] = $code;
            }

            foreach ($seg['sub_segments'] ?? [] as $sub) {
                $subNo = ++$rowNum;
                $rows[] = [
                    '      ' . ($sub['name'] ?? ''),
                    (float) ($sub['weekly_mv']   ?? 0),
                    (float) ($sub['mtd_mv']      ?? 0),
                    (float) ($sub['total_loans'] ?? 0),
                ];
                $this->subRowCodes[$subNo] = $code;
            }
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                /** @var Worksheet $sheet */
                $sheet   = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $hdr     = $this->headerRow;

                $sheet->mergeCells("A{$this->titleRow}:D{$this->titleRow}");
                $sheet->getStyle("A{$this->titleRow}:D{$this->titleRow}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3A5F']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT,
                                    'vertical'   => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension($this->titleRow)->setRowHeight(32);

                $metaStart = $this->titleRow + 2;
                $metaEnd   = $hdr - 2;

                for ($r = $metaStart; $r <= $metaEnd; $r++) {
                    $sheet->getStyle("A{$r}:D{$r}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
                    ]);
                    $sheet->getStyle("A{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '1F3A5F']],
                    ]);
                    $sheet->getStyle("B{$r}")->applyFromArray([
                        'font' => ['color' => ['rgb' => '334155']],
                    ]);
                }

                $sheet->getStyle("A{$hdr}:D{$hdr}")->applyFromArray([
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
                        'wrapText'   => false,
                    ],
                    'borders' => [
                        'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '0F2744']],
                    ],
                ]);
                $sheet->getStyle("A{$hdr}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getRowDimension($hdr)->setRowHeight(20);
                $sheet->freezePane('A' . ($hdr + 1));

                foreach ($this->segRowCodes as $r => $code) {
                    $fill = self::SEG_FILL[$code] ?? self::SEG_FILL['UNMAPPED'];
                    $sheet->getStyle("A{$r}:D{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '0F172A']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fill['row']]],
                        'borders' => [
                            'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
                        ],
                    ]);
                    $sheet->getRowDimension($r)->setRowHeight(18);
                }

                foreach ($this->subRowCodes as $r => $code) {
                    $fill = self::SEG_FILL[$code] ?? self::SEG_FILL['UNMAPPED'];
                    $sheet->getStyle("A{$r}:D{$r}")->applyFromArray([
                        'font' => ['bold' => false, 'size' => 10, 'color' => ['rgb' => '475569']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fill['sub']]],
                        'borders' => [
                            'bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'E2E8F0']],
                        ],
                    ]);
                }

                foreach ($this->totalRows as $r) {
                    $sheet->getStyle("A{$r}:D{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1E3A5F']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                        'borders' => [
                            'top'    => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '94A3B8']],
                            'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '94A3B8']],
                        ],
                    ]);
                    $sheet->getRowDimension($r)->setRowHeight(18);
                }

                $allDataRows = array_merge(
                    array_keys($this->segRowCodes),
                    array_keys($this->subRowCodes),
                    $this->totalRows
                );

                foreach ($allDataRows as $r) {
                    $sheet->getStyle("B{$r}:D{$r}")
                        ->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle("B{$r}:D{$r}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    foreach (['B', 'C'] as $col) {
                        $val = $sheet->getCell("{$col}{$r}")->getValue();
                        if (!is_numeric($val)) continue;
                        $v = (float) $val;
                        if ($v > 0)      $sheet->getStyle("{$col}{$r}")->getFont()->getColor()->setRGB('166534');
                        elseif ($v < 0)  $sheet->getStyle("{$col}{$r}")->getFont()->getColor()->setRGB('991B1B');
                    }

                    $sheet->getStyle("D{$r}")->getFont()->getColor()->setRGB('0F172A');
                }

                if ($lastRow > $hdr) {
                    $sheet->getStyle("A{$hdr}:D{$lastRow}")->applyFromArray([
                        'borders' => [
                            'outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
                        ],
                    ]);
                }
            },
        ];
    }
}

// =============================================================================
// SHEET 2 — CIF Drilldown  (weekly movers per sub-segment, combined KES)
// =============================================================================

class WeeklyLoanDrilldownSheet implements FromArray, WithTitle, WithColumnWidths, WithEvents
{
    private array $mergeRows    = [];
    private array $subHdrRows   = [];
    private array $gainHdrRows  = [];
    private array $lossHdrRows  = [];
    private array $gainDataRows = [];
    private array $lossDataRows = [];

    public function __construct(
        private readonly array $drilldown,
        private readonly array $periods
    ) {}

    public function title(): string { return 'CIF Drilldown'; }

    public function columnWidths(): array
    {
        return ['A' => 6, 'B' => 16, 'C' => 34, 'D' => 10, 'E' => 10, 'F' => 18, 'G' => 18, 'H' => 20];
    }

    public function array(): array
    {
        $weekStart = $this->periods['week_start'] ?? '';
        $weekEnd   = $this->periods['week_end']   ?? '';

        $rows   = [];
        $rowNum = 0;

        $rows[] = ['WEEKLY PERFORMING LOAN CIF MOVERS BY SUB-SEGMENT  (LCY+FCY combined)', '', '', '', '', '', '', ''];
        $this->mergeRows[] = ++$rowNum;

        $rows[] = ["Week: {$weekStart}  →  {$weekEnd}", '', '', '', '', '', '', ''];
        $this->mergeRows[] = ++$rowNum;

        $rows[] = ['', '', '', '', '', '', '', ''];
        $rowNum++;

        foreach ($this->drilldown as $subSegName => $buckets) {
            $gainers = $buckets['gainers'] ?? collect();
            $losers  = $buckets['losers']  ?? collect();

            if ($gainers->isEmpty() && $losers->isEmpty()) continue;

            $rows[] = [strtoupper((string) $subSegName), '', '', '', '', '', '', ''];
            $this->mergeRows[]  = ++$rowNum;
            $this->subHdrRows[] = $rowNum;

            if ($gainers->isNotEmpty()) {
                $rows[] = ['▲  GAINERS', '', '', '', '', '', '', ''];
                $this->mergeRows[]  = ++$rowNum;
                $this->gainHdrRows[] = $rowNum;

                $rows[] = ['#', 'CIF', 'Customer Name', 'Branch', '', 'Start Balance', 'End Balance', 'Weekly Mv'];
                $this->gainHdrRows[] = ++$rowNum;

                foreach ($gainers as $i => $r) {
                    $rows[] = [
                        $i + 1,
                        (string) ($r->cif           ?? ''),
                        (string) ($r->customer_name ?? ''),
                        (string) ($r->branch_code   ?? ''),
                        '',
                        (float)  ($r->start_balance ?? 0),
                        (float)  ($r->end_balance   ?? 0),
                        (float)  ($r->movement      ?? 0),
                    ];
                    $this->gainDataRows[] = ++$rowNum;
                }
            }

            if ($losers->isNotEmpty()) {
                $rows[] = ['▼  LOSERS', '', '', '', '', '', '', ''];
                $this->mergeRows[]  = ++$rowNum;
                $this->lossHdrRows[] = $rowNum;

                $rows[] = ['#', 'CIF', 'Customer Name', 'Branch', '', 'Start Balance', 'End Balance', 'Weekly Mv'];
                $this->lossHdrRows[] = ++$rowNum;

                foreach ($losers as $i => $r) {
                    $rows[] = [
                        $i + 1,
                        (string) ($r->cif           ?? ''),
                        (string) ($r->customer_name ?? ''),
                        (string) ($r->branch_code   ?? ''),
                        '',
                        (float)  ($r->start_balance ?? 0),
                        (float)  ($r->end_balance   ?? 0),
                        (float)  ($r->movement      ?? 0),
                    ];
                    $this->lossDataRows[] = ++$rowNum;
                }
            }

            $rows[] = ['', '', '', '', '', '', '', ''];
            $this->mergeRows[] = ++$rowNum;
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                foreach ($this->mergeRows as $r) {
                    $sheet->mergeCells("A{$r}:H{$r}");
                }

                $sheet->getStyle('A1:H1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3A5F']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT,
                                    'vertical'   => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(24);

                $sheet->getStyle('A2:H2')->applyFromArray([
                    'font' => ['size' => 10, 'italic' => true, 'color' => ['rgb' => '475569']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                ]);

                foreach ($this->subHdrRows as $r) {
                    $sheet->getStyle("A{$r}:H{$r}")->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT,
                                        'vertical'   => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getRowDimension($r)->setRowHeight(18);
                }

                foreach ($this->gainHdrRows as $r) {
                    $sheet->getStyle("A{$r}:H{$r}")->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '166534']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                                        'vertical'   => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }

                foreach ($this->lossHdrRows as $r) {
                    $sheet->getStyle("A{$r}:H{$r}")->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '991B1B']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                                        'vertical'   => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }

                foreach ($this->gainDataRows as $r) {
                    $sheet->getStyle("A{$r}:H{$r}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
                    ]);
                    $this->styleDataRow($sheet, $r, true);
                }

                foreach ($this->lossDataRows as $r) {
                    $sheet->getStyle("A{$r}:H{$r}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF1F2']],
                    ]);
                    $this->styleDataRow($sheet, $r, false);
                }

                $sheet->freezePane('A4');
            },
        ];
    }

    private function styleDataRow(Worksheet $sheet, int $r, bool $isGain): void
    {
        $sheet->getStyle("F{$r}:H{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("F{$r}:H{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("H{$r}")->getFont()->getColor()->setRGB($isGain ? '166534' : '991B1B');
        $sheet->getStyle("A{$r}:H{$r}")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('E2E8F0');
    }
}
