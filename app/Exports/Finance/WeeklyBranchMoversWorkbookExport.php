<?php

declare(strict_types=1);

namespace App\Exports\Finance;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Weekly branch workbook (deposits only): the branch summary sheet, then each branch's top 10
 * deposit customer movers over the WTD window — the same "Deposit CIF" sheet the daily branch
 * movers workbook uses (Corporate and staff excluded).
 */
class WeeklyBranchMoversWorkbookExport implements Export, WithMultipleSheets
{
    private const CIF_LIMIT = 10;

    /** @param array $periods ['week'|'mtd'|'ytd' => ['start','end','label']] */
    /** @param array $data    ['week'|'mtd'|'ytd' => ['summary','topGainers','topLosers']] */
    public function __construct(
        private readonly string $weekEnd,
        private readonly array  $periods,
        private readonly array  $data,
        private readonly int    $limit = 10
    ) {}

    public function sheets(): array
    {
        $weekStart = (string) ($this->periods['week']['start'] ?? $this->weekEnd);
        $weekEnd   = (string) ($this->periods['week']['end']   ?? $this->weekEnd);

        // The CIF sheet class is declared inside BranchMoversWorkbookExport.php, so it
        // only autoloads once that file is loaded.
        class_exists(BranchMoversWorkbookExport::class);

        return [
            new WeeklyBranchMoversSummarySheet($this->weekEnd, $this->periods, $this->data, $this->limit),
            new CifMoversByBranchSheet($weekStart, $weekEnd, self::CIF_LIMIT),
        ];
    }
}

/**
 * Branch summary (Deposits WTD/MTD/YTD Δ + Closing Balance) followed by the week's top
 * gainers/losers.
 *
 * Columns: A Branch Code, B Branch Name, C Deposits WTD Δ, D MTD Δ, E YTD Δ, F Closing Bal.
 */
class WeeklyBranchMoversSummarySheet implements FromArray, WithTitle, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    private const NUM_COLS = 6;

    private array $boldRows       = [];
    private int   $headerRow      = 0;
    private int   $lastBranchRow  = 0;
    private array $gainDataRows   = [];
    private array $lossDataRows   = [];

    /** @param array $periods ['week'|'mtd'|'ytd' => ['start','end','label']] */
    /** @param array $data    ['week'|'mtd'|'ytd' => ['summary','topGainers','topLosers']] */
    public function __construct(
        private readonly string $weekEnd,
        private readonly array  $periods,
        private readonly array  $data,
        private readonly int    $limit = 10
    ) {}

    public function title(): string { return 'Weekly Branch Movers'; }

    public function array(): array
    {
        $weekPeriod = $this->periods['week'] ?? [];
        $mtdPeriod  = $this->periods['mtd']  ?? [];
        $ytdPeriod  = $this->periods['ytd']  ?? [];

        $weekData = $this->data['week'] ?? ['summary' => collect(), 'topGainers' => collect(), 'topLosers' => collect()];
        $mtdData  = $this->data['mtd']  ?? ['summary' => collect()];
        $ytdData  = $this->data['ytd']  ?? ['summary' => collect()];

        // Build branch map: code → [name, dep_week/mtd/ytd, dep_balance]
        $map = [];

        foreach (['week' => $weekData, 'mtd' => $mtdData, 'ytd' => $ytdData] as $key => $periodData) {
            foreach (collect($periodData['summary'] ?? []) as $r) {
                $code = strtoupper(trim((string) ($r->group_key ?? '')));
                if ($code === '') continue;
                if (!isset($map[$code])) {
                    $map[$code] = [
                        'code' => $code, 'name' => (string) ($r->group_name ?? $code),
                        'dep_week' => 0, 'dep_mtd' => 0, 'dep_ytd' => 0, 'dep_balance' => 0,
                    ];
                }
                $map[$code]['name']       = (string) ($r->group_name ?? $map[$code]['name']);
                $map[$code]["dep_{$key}"] = (float) ($r->movement ?? 0);
                if ($key === 'week') {
                    $map[$code]['dep_balance'] = (float) ($r->end_balance ?? 0);
                }
            }
        }

        // Sort: regular branches (P-prefix or others), then 834, 950, ALL
        uksort($map, function ($a, $b) {
            $special = ['834' => 1, '950' => 2, 'ALL' => 99];
            $as = $special[$a] ?? 0;
            $bs = $special[$b] ?? 0;
            if ($as !== $bs) return $as - $bs;
            return strcmp($a, $b);
        });

        $rows   = [];
        $rowNum = 0;

        // Title
        $rows[] = array_pad(['ECOBANK KENYA — WEEKLY BRANCH MOVERS (DEPOSITS)'], self::NUM_COLS, '');
        $this->boldRows[] = ++$rowNum;

        $rows[] = array_pad(["Week ending: {$this->weekEnd}"], self::NUM_COLS, '');
        ++$rowNum;

        $rows[] = array_pad([
            "Weekly: {$weekPeriod['start']} → {$weekPeriod['end']}",
            "MTD: {$mtdPeriod['start']} → {$mtdPeriod['end']}",
            "YTD: {$ytdPeriod['start']} → {$ytdPeriod['end']}",
        ], self::NUM_COLS, '');
        ++$rowNum;

        $rows[] = array_fill(0, self::NUM_COLS, '');
        ++$rowNum;

        // Column header
        $this->headerRow = ++$rowNum;
        $rows[] = [
            'Branch Code', 'Branch Name',
            'Deposits WTD Δ', 'Deposits MTD Δ', 'Deposits YTD Δ', 'Deposits Closing Bal',
        ];
        $this->boldRows[] = $this->headerRow;

        foreach ($map as $b) {
            ++$rowNum;
            $rows[] = [
                (string) $b['code'],
                (string) $b['name'],
                (float)  $b['dep_week'],
                (float)  $b['dep_mtd'],
                (float)  $b['dep_ytd'],
                (float)  $b['dep_balance'],
            ];
        }
        $this->lastBranchRow = $rowNum;

        $rows[] = array_fill(0, self::NUM_COLS, '');
        ++$rowNum;

        // Top gainers / losers (week only)
        $topGainers = collect($weekData['topGainers'] ?? []);
        $topLosers  = collect($weekData['topLosers']  ?? []);

        $gHeaderRow = ++$rowNum;
        $rows[] = array_pad(["TOP {$this->limit} WEEKLY GAINERS"], self::NUM_COLS, '');
        $this->boldRows[] = $gHeaderRow;

        $gTableRow = ++$rowNum;
        $rows[] = array_pad(['Rank', 'Branch Code', 'Branch Name', 'Start Balance', 'End Balance', 'Movement'], self::NUM_COLS, '');
        $this->boldRows[] = $gTableRow;

        if ($topGainers->isEmpty()) {
            $rows[] = array_pad(['', '(no data)'], self::NUM_COLS, '');
            ++$rowNum;
        } else {
            foreach ($topGainers as $r) {
                $rows[] = array_pad([
                    (int)    ($r->rank          ?? 0),
                    (string) ($r->group_key     ?? ''),
                    (string) ($r->group_name    ?? ''),
                    (float)  ($r->start_balance ?? 0),
                    (float)  ($r->end_balance   ?? 0),
                    (float)  ($r->movement      ?? 0),
                ], self::NUM_COLS, '');
                ++$rowNum;
                $this->gainDataRows[] = $rowNum;
            }
        }

        $rows[] = array_fill(0, self::NUM_COLS, '');
        ++$rowNum;

        $lHeaderRow = ++$rowNum;
        $rows[] = array_pad(["TOP {$this->limit} WEEKLY LOSERS"], self::NUM_COLS, '');
        $this->boldRows[] = $lHeaderRow;

        $lTableRow = ++$rowNum;
        $rows[] = array_pad(['Rank', 'Branch Code', 'Branch Name', 'Start Balance', 'End Balance', 'Movement'], self::NUM_COLS, '');
        $this->boldRows[] = $lTableRow;

        if ($topLosers->isEmpty()) {
            $rows[] = array_pad(['', '(no data)'], self::NUM_COLS, '');
            ++$rowNum;
        } else {
            foreach ($topLosers as $r) {
                $rows[] = array_pad([
                    (int)    ($r->rank          ?? 0),
                    (string) ($r->group_key     ?? ''),
                    (string) ($r->group_name    ?? ''),
                    (float)  ($r->start_balance ?? 0),
                    (float)  ($r->end_balance   ?? 0),
                    (float)  ($r->movement      ?? 0),
                ], self::NUM_COLS, '');
                ++$rowNum;
                $this->lossDataRows[] = $rowNum;
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
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                /** @var Worksheet $sheet */
                $sheet   = $event->sheet->getDelegate();
                $hdr     = $this->headerRow;
                $lastRow = $sheet->getHighestRow();

                // Title row
                $sheet->mergeCells('A1:F1');
                $sheet->getStyle('A1:F1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '002E4A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(26);
                $sheet->mergeCells('A2:F2');

                foreach ($this->boldRows as $r) {
                    $sheet->getStyle("A{$r}:F{$r}")->getFont()->setBold(true);
                }

                // Column header row
                $sheet->getStyle("A{$hdr}:F{$hdr}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3A5F']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("A{$hdr}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Deposits header tint
                $sheet->getStyle("C{$hdr}:F{$hdr}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']]]);

                // Branch data rows
                if ($this->lastBranchRow > $hdr) {
                    for ($row = $hdr + 1; $row <= $this->lastBranchRow; $row++) {
                        // Colour only the movement columns by sign — not the closing-balance column (F)
                        foreach (['C', 'D', 'E'] as $col) {
                            $v = $sheet->getCell("{$col}{$row}")->getValue();
                            if (!is_numeric($v)) continue;
                            $vf = (float) $v;
                            if ($vf > 0)     $sheet->getStyle("{$col}{$row}")->getFont()->getColor()->setRGB('0B6E4F');
                            elseif ($vf < 0) $sheet->getStyle("{$col}{$row}")->getFont()->getColor()->setRGB('B00020');
                        }
                        $sheet->getStyle("C{$row}:F{$row}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']]]);
                        $sheet->getStyle("F{$row}")->getFont()->getColor()->setRGB('374151');
                    }

                    // ALL row (last branch row) — bold + light grey
                    $sheet->getStyle("A{$this->lastBranchRow}:F{$this->lastBranchRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                    ]);
                }

                // Top gainers / losers data rows — colour the Movement column (F)
                foreach ($this->gainDataRows as $r) {
                    $sheet->getStyle("A{$r}:F{$r}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']]]);
                    $sheet->getStyle("F{$r}")->getFont()->getColor()->setRGB('166534');
                }
                foreach ($this->lossDataRows as $r) {
                    $sheet->getStyle("A{$r}:F{$r}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF1F2']]]);
                    $sheet->getStyle("F{$r}")->getFont()->getColor()->setRGB('991B1B');
                }

                if ($lastRow > $hdr) {
                    $sheet->getStyle("A{$hdr}:F{$lastRow}")->getBorders()->getAllBorders()
                        ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('E2E8F0');
                }

                $sheet->freezePane('A' . ($hdr + 1));
            },
        ];
    }
}
