<?php

declare(strict_types=1);

namespace App\Exports\Finance;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WeeklySegmentWorkbookExport implements WithMultipleSheets
{
    public function __construct(
        private readonly array $data,
        private readonly array $drilldown,
        private readonly array $historicalSection = []
    ) {}

    public function sheets(): array
    {
        return [
            new WeeklySegmentSummarySheet($this->data, 'bank'),
            new WeeklySegmentSummarySheet($this->data, 'lcy'),
            new WeeklySegmentSummarySheet($this->data, 'fcy'),
            new WeeklySegmentDrilldownSheet($this->drilldown, $this->data['periods'] ?? []),
            new WeeklySegmentHistoricalSheet($this->historicalSection),
        ];
    }
}

// =============================================================================
// SHEET 1 — Bank / LCY / FCY Summary
// =============================================================================

class WeeklySegmentSummarySheet implements FromArray, WithTitle, WithColumnWidths, WithEvents
{
    // Rows that are the 3 main segments (CB / CM / CS) + OT + ALL
    private array $segRowCodes = [];   // [rowNo => segCode]  — SEGMENT level only
    private array $subRowCodes = [];   // [rowNo => segCode]  — sub-segment level
    private array $totalRows   = [];   // [rowNo]             — ALL/Totals
    private int   $headerRow   = 0;
    private int   $titleRow    = 0;

    // Segment fill colours (Excel hex, no #)
    private const SEG_FILL = [
        'CB'  => ['row' => 'DBEAFE', 'sub' => 'EFF6FF'],  // blue
        'CM'  => ['row' => 'EDE9FE', 'sub' => 'F5F3FF'],  // purple
        'CS'  => ['row' => 'FEF3C7', 'sub' => 'FFFBEB'],  // amber
        'OT'  => ['row' => 'F1F5F9', 'sub' => 'F8FAFC'],  // gray
        'ALL' => ['row' => 'E2E8F0', 'sub' => 'E2E8F0'],  // darker gray
    ];

    // The 3 segment codes that should receive bold segment-level treatment
    private const SEGMENT_CODES = ['CB', 'CM', 'CS', 'OT', 'ALL'];

    public function __construct(
        private readonly array $data,
        private readonly string $currency = 'lcy'
    ) {}

    public function title(): string
    {
        return match ($this->currency) {
            'bank'  => 'Bank Summary',
            'fcy'   => 'FCY Summary',
            default => 'LCY Summary',
        };
    }

    public function columnWidths(): array
    {
        return ['A' => 40, 'B' => 20, 'C' => 20, 'D' => 20, 'E' => 24];
    }

    public function array(): array
    {
        $periods = $this->data['periods'] ?? [];
        $rows    = [];
        $rowNum  = 0;  // track the REAL row number being added

        $titleText = match ($this->currency) {
            'bank'  => 'WEEKLY SEGMENT MOVEMENT REPORT — BANK (ALL CURRENCIES, KES EQUIVALENT)',
            'fcy'   => 'WEEKLY SEGMENT MOVEMENT REPORT — FCY (KES EQUIVALENT)',
            default => 'WEEKLY SEGMENT MOVEMENT REPORT',
        };

        // ── Title block ───────────────────────────────────────────────────
        $this->titleRow = ++$rowNum;
        $rows[] = [$titleText, '', '', '', ''];

        $rows[] = ['', '', '', '', ''];  // spacer — explicit empty strings keep the row
        $rowNum++;

        $rows[] = ['Week',    ($periods['week_start'] ?? '') . '  →  ' . ($periods['week_end'] ?? ''), '', '', ''];
        $rowNum++;

        $rows[] = ['MTD from', $periods['mtd_start'] ?? '', '', '', ''];
        $rowNum++;

        $rows[] = ['YTD from', $periods['ytd_start'] ?? '', '', '', ''];
        $rowNum++;

        $rows[] = ['Generated', now()->timezone(config('app.timezone', 'Africa/Nairobi'))->format('d M Y  H:i'), '', '', ''];
        $rowNum++;

        $rows[] = ['', '', '', '', ''];  // spacer
        $rowNum++;

        // ── Column header ──────────────────────────────────────────────────
        $depositsColLabel = match ($this->currency) {
            'bank'  => 'Total Bank Deposits (closing)',
            'fcy'   => 'Total FCY Deposits (KES eq., closing)',
            default => 'Total Deposits (closing)',
        };

        $this->headerRow = ++$rowNum;
        $rows[] = [
            'Segment / Sub-Segment',
            'Weekly Movement',
            'MTD Movement',
            'YTD Movement',
            $depositsColLabel,
        ];

        // ── Data ───────────────────────────────────────────────────────────
        $segments = $this->data[$this->currency]['segments'] ?? [];

        foreach ($segments as $seg) {
            $code    = $seg['code'] ?? 'OT';
            $isTotal = ($code === 'ALL');
            $rowNo   = ++$rowNum;

            $rows[] = [
                $seg['name'] ?? $code,
                (float) ($seg['weekly_mv']      ?? 0),
                (float) ($seg['mtd_mv']          ?? 0),
                (float) ($seg['ytd_mv']          ?? 0),
                (float) ($seg['total_deposits']   ?? 0),
            ];

            if ($isTotal) {
                $this->totalRows[] = $rowNo;
            } else {
                // Only CB / CM / CS / OT go here — these are the segment-level bold rows
                $this->segRowCodes[$rowNo] = $code;
            }

            foreach ($seg['sub_segments'] ?? [] as $sub) {
                $subNo = ++$rowNum;
                $rows[] = [
                    '      ' . ($sub['name'] ?? ''),   // extra indent for visual hierarchy
                    (float) ($sub['weekly_mv']      ?? 0),
                    (float) ($sub['mtd_mv']          ?? 0),
                    (float) ($sub['ytd_mv']          ?? 0),
                    (float) ($sub['total_deposits']   ?? 0),
                ];
                // Sub-segment rows: tracked separately, NEVER bold
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

                // ── Title ──────────────────────────────────────────────────
                $sheet->mergeCells("A{$this->titleRow}:E{$this->titleRow}");
                $sheet->getStyle("A{$this->titleRow}:E{$this->titleRow}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3A5F']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT,
                                    'vertical'   => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension($this->titleRow)->setRowHeight(32);

                // ── Meta rows (rows 3–7 based on structure) ────────────────
                // Rows between title+spacer and column header
                $metaStart = $this->titleRow + 2;   // skip title + spacer
                $metaEnd   = $hdr - 2;              // skip spacer before header

                for ($r = $metaStart; $r <= $metaEnd; $r++) {
                    $sheet->getStyle("A{$r}:E{$r}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
                    ]);
                    $sheet->getStyle("A{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '1F3A5F']],
                    ]);
                    $sheet->getStyle("B{$r}")->applyFromArray([
                        'font' => ['color' => ['rgb' => '334155']],
                    ]);
                }

                // ── Column header ──────────────────────────────────────────
                $sheet->getStyle("A{$hdr}:E{$hdr}")->applyFromArray([
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

                // ── SEGMENT rows: Corporate / Commercial / Consumer / Others ──
                // These are the ONLY rows that get bold + colour
                foreach ($this->segRowCodes as $r => $code) {
                    $fill = self::SEG_FILL[$code] ?? self::SEG_FILL['OT'];
                    $sheet->getStyle("A{$r}:E{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '0F172A']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fill['row']]],
                        'borders' => [
                            'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
                        ],
                    ]);
                    $sheet->getRowDimension($r)->setRowHeight(18);
                }

                // ── SUB-SEGMENT rows: plain, indented, lighter ─────────────
                // NOT bold — these are business_segment_name groupings
                foreach ($this->subRowCodes as $r => $code) {
                    $fill = self::SEG_FILL[$code] ?? self::SEG_FILL['OT'];
                    $sheet->getStyle("A{$r}:E{$r}")->applyFromArray([
                        'font' => ['bold' => false, 'size' => 10, 'color' => ['rgb' => '475569']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fill['sub']]],
                        'borders' => [
                            'bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'E2E8F0']],
                        ],
                    ]);
                }

                // ── TOTALS row ─────────────────────────────────────────────
                foreach ($this->totalRows as $r) {
                    $sheet->getStyle("A{$r}:E{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1E3A5F']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                        'borders' => [
                            'top'    => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '94A3B8']],
                            'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '94A3B8']],
                        ],
                    ]);
                    $sheet->getRowDimension($r)->setRowHeight(18);
                }

                // ── Number format, alignment, movement colours ─────────────
                $allDataRows = array_merge(
                    array_keys($this->segRowCodes),
                    array_keys($this->subRowCodes),
                    $this->totalRows
                );

                foreach ($allDataRows as $r) {
                    $sheet->getStyle("B{$r}:E{$r}")
                        ->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle("B{$r}:E{$r}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    // Movement cols B–D: green for gains, red for losses
                    foreach (['B', 'C', 'D'] as $col) {
                        $val = $sheet->getCell("{$col}{$r}")->getValue();
                        if (!is_numeric($val)) continue;
                        $v = (float) $val;
                        if ($v > 0)      $sheet->getStyle("{$col}{$r}")->getFont()->getColor()->setRGB('166534');
                        elseif ($v < 0)  $sheet->getStyle("{$col}{$r}")->getFont()->getColor()->setRGB('991B1B');
                    }

                    // Total Deposits (E): always dark, bold on segment/total rows
                    $sheet->getStyle("E{$r}")->getFont()->getColor()->setRGB('0F172A');
                }

                // ── Outer border around data table ─────────────────────────
                if ($lastRow > $hdr) {
                    $sheet->getStyle("A{$hdr}:E{$lastRow}")->applyFromArray([
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
// SHEET — CIF Drilldown  (flat top 100 gainers / top 100 losers, whole bank, week only)
// =============================================================================

class WeeklySegmentDrilldownSheet implements FromArray, WithTitle, WithColumnWidths, WithEvents
{
    private array $mergeRows     = [];
    private array $gainHdrRows   = [];
    private array $lossHdrRows   = [];
    private array $gainDataRows  = [];
    private array $lossDataRows  = [];

    public function __construct(
        private readonly array $drilldown,
        private readonly array $periods
    ) {}

    public function title(): string { return 'CIF Drilldown'; }

    public function columnWidths(): array
    {
        return ['A' => 6, 'B' => 16, 'C' => 34, 'D' => 10, 'E' => 18, 'F' => 18, 'G' => 18];
    }

    public function array(): array
    {
        $weekStart = $this->periods['week_start'] ?? '';
        $weekEnd   = $this->periods['week_end']   ?? '';

        $gainers = collect($this->drilldown['gainers'] ?? []);
        $losers  = collect($this->drilldown['losers']  ?? []);

        $rows   = [];
        $rowNum = 0;

        $rows[] = ['CIF DRILLDOWN — TOP 100 GAINERS & LOSERS  (WHOLE BANK, ANY CURRENCY)', '', '', '', '', '', ''];
        $this->mergeRows[] = ++$rowNum;

        $rows[] = ["Week: {$weekStart}  →  {$weekEnd}", '', '', '', '', '', ''];
        $this->mergeRows[] = ++$rowNum;

        $rows[] = ['', '', '', '', '', '', ''];  // spacer
        $rowNum++;

        if ($gainers->isNotEmpty()) {
            $rows[] = ['▲  GAINERS', '', '', '', '', '', ''];
            $this->mergeRows[]   = ++$rowNum;
            $this->gainHdrRows[] = $rowNum;

            $rows[] = ['#', 'CIF', 'Customer Name', 'Branch', 'Start Balance', 'End Balance', 'Weekly Mv'];
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
            $rows[] = ['', '', '', '', '', '', ''];  // spacer
            $rowNum++;

            $rows[] = ['▼  LOSERS', '', '', '', '', '', ''];
            $this->mergeRows[]   = ++$rowNum;
            $this->lossHdrRows[] = $rowNum;

            $rows[] = ['#', 'CIF', 'Customer Name', 'Branch', 'Start Balance', 'End Balance', 'Weekly Mv'];
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

        return $rows;
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

                // Title (row 1)
                $sheet->getStyle('A1:G1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3A5F']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT,
                                    'vertical'   => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(24);

                // Period row (row 2)
                $sheet->getStyle('A2:G2')->applyFromArray([
                    'font' => ['size' => 10, 'italic' => true, 'color' => ['rgb' => '475569']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                ]);

                // Gainers section label + column header: green
                foreach ($this->gainHdrRows as $r) {
                    $sheet->getStyle("A{$r}:G{$r}")->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '166534']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                                        'vertical'   => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }

                // Losers section label + column header: red
                foreach ($this->lossHdrRows as $r) {
                    $sheet->getStyle("A{$r}:G{$r}")->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '991B1B']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                                        'vertical'   => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }

                // Gainers data: light green tint
                foreach ($this->gainDataRows as $r) {
                    $sheet->getStyle("A{$r}:G{$r}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
                    ]);
                    $this->styleDataRow($sheet, $r, true);
                }

                // Losers data: light red tint
                foreach ($this->lossDataRows as $r) {
                    $sheet->getStyle("A{$r}:G{$r}")->applyFromArray([
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
        $sheet->getStyle("E{$r}:G{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("E{$r}:G{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("G{$r}")->getFont()->getColor()->setRGB($isGain ? '166534' : '991B1B');
        $sheet->getStyle("A{$r}:G{$r}")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('E2E8F0');
    }
}

// =============================================================================
// SHEET 3 — Historical Comparison (LCY + FCY)
// =============================================================================

class WeeklySegmentHistoricalSheet implements FromArray, WithTitle, WithColumnWidths, WithEvents
{
    // Tracked rows — arrays of [rowNo => segCode] or [rowNo] for totals/section hdrs
    private array $segRowCodes  = [];
    private array $subRowCodes  = [];
    private array $totalRows    = [];
    private array $sectionHdrs  = [];   // LCY / FCY section header rows
    private int   $titleRow     = 0;

    private const SEG_FILL = [
        'CB'  => ['row' => 'DBEAFE', 'sub' => 'EFF6FF'],
        'CM'  => ['row' => 'EDE9FE', 'sub' => 'F5F3FF'],
        'CS'  => ['row' => 'FEF3C7', 'sub' => 'FFFBEB'],
        'OT'  => ['row' => 'F1F5F9', 'sub' => 'F8FAFC'],
        'ALL' => ['row' => '1B2A3B', 'sub' => '1B2A3B'],
    ];

    public function __construct(private readonly array $historicalSection) {}

    public function title(): string { return 'Historical Comparison'; }

    public function columnWidths(): array
    {
        return ['A' => 42, 'B' => 22, 'C' => 20, 'D' => 20, 'E' => 20, 'F' => 22];
    }

    public function array(): array
    {
        $labels       = $this->historicalSection['labels']          ?? [];
        $bankSegments = $this->historicalSection['bank']['segments'] ?? [];
        $lcySegments  = $this->historicalSection['lcy']['segments']  ?? [];
        $fcySegments  = $this->historicalSection['fcy']['segments']  ?? [];

        $hdr = [
            'Segment / Sub-Segment',
            $labels['ye'] ?? 'YE Balance',
            $labels['m3'] ?? 'Month-3',
            $labels['m2'] ?? 'Month-2',
            $labels['m1'] ?? 'Month-1',
            $labels['w1'] ?? 'W-1 Movement',
        ];

        $rows   = [];
        $rowNum = 0;

        // Main title
        $this->titleRow = ++$rowNum;
        $rows[] = ['HISTORICAL COMPARISON — DEPOSITS', '', '', '', '', ''];

        $rows[] = ['Generated', now()->timezone(config('app.timezone', 'Africa/Nairobi'))->format('d M Y  H:i'), '', '', '', ''];
        $rowNum++;

        $rows[] = ['', '', '', '', '', ''];
        $rowNum++;

        $sections = [
            ['label' => 'BANK DEPOSITS (ALL CURRENCIES, KES EQUIVALENT)', 'segments' => $bankSegments],
            ['label' => 'LCY DEPOSITS (KES)',                             'segments' => $lcySegments],
            ['label' => 'FCY DEPOSITS (KES EQUIVALENT)',                  'segments' => $fcySegments],
        ];

        foreach ($sections as $i => $section) {
            $this->sectionHdrs[] = ++$rowNum;
            $rows[] = [$section['label'], '', '', '', '', ''];

            $rows[] = $hdr;
            $rowNum++;

            $rowNum = $this->appendSegments($rows, $rowNum, $section['segments']);

            if ($i < count($sections) - 1) {
                $rows[] = ['', '', '', '', '', ''];
                $rowNum++;
            }
        }

        return $rows;
    }

    private function appendSegments(array &$rows, int $rowNum, array $segments): int
    {
        foreach ($segments as $seg) {
            $code    = $seg['code'] ?? 'OT';
            $isTotal = ($code === 'ALL');
            $rowNo   = ++$rowNum;

            $rows[] = [
                $seg['name'] ?? $code,
                (float) ($seg['ye_bal'] ?? 0),
                (float) ($seg['m3_bal'] ?? 0),
                (float) ($seg['m2_bal'] ?? 0),
                (float) ($seg['m1_bal'] ?? 0),
                (float) ($seg['w1_mv']  ?? 0),
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
                    (float) ($sub['ye_bal'] ?? 0),
                    (float) ($sub['m3_bal'] ?? 0),
                    (float) ($sub['m2_bal'] ?? 0),
                    (float) ($sub['m1_bal'] ?? 0),
                    (float) ($sub['w1_mv']  ?? 0),
                ];
                $this->subRowCodes[$subNo] = $code;
            }
        }
        return $rowNum;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                /** @var Worksheet $sheet */
                $sheet   = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                // Main title
                $sheet->mergeCells("A{$this->titleRow}:F{$this->titleRow}");
                $sheet->getStyle("A{$this->titleRow}:F{$this->titleRow}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3A5F']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension($this->titleRow)->setRowHeight(32);

                // Meta row (Generated)
                $sheet->getStyle('A2:F2')->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
                    'font' => ['color' => ['rgb' => '64748B']],
                ]);

                // Section header rows (LCY / FCY)
                foreach ($this->sectionHdrs as $r) {
                    $sheet->mergeCells("A{$r}:F{$r}");
                    $sheet->getStyle("A{$r}:F{$r}")->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F2744']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getRowDimension($r)->setRowHeight(22);

                    // Column header row immediately below the section header
                    $hdrRow = $r + 1;
                    $sheet->getStyle("A{$hdrRow}:F{$hdrRow}")->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3A5F']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                        'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '0F2744']]],
                    ]);
                    $sheet->getStyle("A{$hdrRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getRowDimension($hdrRow)->setRowHeight(24);
                }

                // Freeze after first column header row
                if (!empty($this->sectionHdrs)) {
                    $sheet->freezePane('A' . ($this->sectionHdrs[0] + 2));
                }

                // Segment rows
                foreach ($this->segRowCodes as $r => $code) {
                    $fill = self::SEG_FILL[$code] ?? self::SEG_FILL['OT'];
                    $sheet->getStyle("A{$r}:F{$r}")->applyFromArray([
                        'font'    => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '0F172A']],
                        'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fill['row']]],
                        'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                    ]);
                    $sheet->getRowDimension($r)->setRowHeight(18);
                }

                // Sub-segment rows
                foreach ($this->subRowCodes as $r => $code) {
                    $fill = self::SEG_FILL[$code] ?? self::SEG_FILL['OT'];
                    $sheet->getStyle("A{$r}:F{$r}")->applyFromArray([
                        'font'    => ['bold' => false, 'size' => 10, 'color' => ['rgb' => '475569']],
                        'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fill['sub']]],
                        'borders' => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'E2E8F0']]],
                    ]);
                }

                // Totals rows — dark background, white bold text
                foreach ($this->totalRows as $r) {
                    $sheet->getStyle("A{$r}:F{$r}")->applyFromArray([
                        'font'    => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                        'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1B2A3B']],
                        'borders' => [
                            'top'    => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '94A3B8']],
                            'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '94A3B8']],
                        ],
                    ]);
                    $sheet->getRowDimension($r)->setRowHeight(20);
                }

                // Number format + alignment + movement colours
                $allDataRows = array_merge(array_keys($this->segRowCodes), array_keys($this->subRowCodes), $this->totalRows);
                foreach ($allDataRows as $r) {
                    $sheet->getStyle("B{$r}:F{$r}")->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle("B{$r}:F{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    // Balance cols B–E: dark on non-total, white on total
                    $isTotal = in_array($r, $this->totalRows);
                    foreach (['B', 'C', 'D', 'E'] as $col) {
                        $sheet->getStyle("{$col}{$r}")->getFont()->getColor()->setRGB($isTotal ? 'FFFFFF' : '0F172A');
                    }

                    // W-1 movement col F: green/red
                    $val = $sheet->getCell("F{$r}")->getValue();
                    if (is_numeric($val)) {
                        $v = (float) $val;
                        if ($v > 0)      $sheet->getStyle("F{$r}")->getFont()->getColor()->setRGB($isTotal ? '86EFAC' : '166534');
                        elseif ($v < 0)  $sheet->getStyle("F{$r}")->getFont()->getColor()->setRGB($isTotal ? 'FCA5A5' : '991B1B');
                    }
                }

                // Outer border
                if ($lastRow > 1) {
                    $sheet->getStyle("A1:F{$lastRow}")->applyFromArray([
                        'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                    ]);
                }
            },
        ];
    }
}
