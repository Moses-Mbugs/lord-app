<?php

declare(strict_types=1);

namespace App\Exports\Finance;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Single-sheet workbook: branch summary (Deposits WTD/MTD Δ + Closing Balance, Loans
 * WTD/MTD Δ + Closing Balance, NTB WTD/MTD/YTD) followed by the week's top gainers/losers.
 *
 * Columns: A Branch Code, B Branch Name, C-E Deposits (WTD Δ/MTD Δ/Closing Bal), F-H
 * Loans (WTD Δ/MTD Δ/Closing Bal), I-K NTB (WTD/MTD/YTD).
 */
class WeeklyBranchMoversWorkbookExport implements FromArray, WithTitle, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    private const NUM_COLS = 11;

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

        // Build branch map: code → [name, dep_week/mtd/balance, loan_week/mtd/balance, ntb_week/mtd/ytd]
        $map = [];

        foreach (['week' => $weekData, 'mtd' => $mtdData, 'ytd' => $ytdData] as $key => $periodData) {
            foreach (collect($periodData['summary'] ?? []) as $r) {
                $code = strtoupper(trim((string) ($r->group_key ?? '')));
                if ($code === '') continue;
                if (!isset($map[$code])) {
                    $map[$code] = [
                        'code' => $code, 'name' => (string) ($r->group_name ?? $code),
                        'dep_week' => 0, 'dep_mtd' => 0, 'dep_balance' => 0,
                        'loan_week' => 0, 'loan_mtd' => 0, 'loan_balance' => 0,
                        'ntb_week' => 0, 'ntb_mtd' => 0, 'ntb_ytd' => 0,
                    ];
                }
                $map[$code]['name']       = (string) ($r->group_name ?? $map[$code]['name']);
                $map[$code]["ntb_{$key}"] = (int) ($r->ntb_count ?? 0);
                if ($key !== 'ytd') {
                    $map[$code]["dep_{$key}"]  = (float) ($r->movement      ?? 0);
                    $map[$code]["loan_{$key}"] = (float) ($r->loan_movement ?? 0);
                }
                if ($key === 'week') {
                    $map[$code]['dep_balance']  = (float) ($r->end_balance ?? 0);
                    $map[$code]['loan_balance'] = (float) ($r->loan_close  ?? 0);
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
        $rows[] = array_pad(['ECOBANK KENYA — WEEKLY BRANCH MOVERS'], self::NUM_COLS, '');
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
            'Deposits WTD Δ', 'Deposits MTD Δ', 'Deposits Closing Bal',
            'Loans WTD Δ', 'Loans MTD Δ', 'Loans Closing Bal',
            'NTB WTD', 'NTB MTD', 'NTB YTD',
        ];
        $this->boldRows[] = $this->headerRow;

        foreach ($map as $b) {
            ++$rowNum;
            $rows[] = [
                (string) $b['code'],
                (string) $b['name'],
                (float)  $b['dep_week'],
                (float)  $b['dep_mtd'],
                (float)  $b['dep_balance'],
                (float)  $b['loan_week'],
                (float)  $b['loan_mtd'],
                (float)  $b['loan_balance'],
                (int)    $b['ntb_week'],
                (int)    $b['ntb_mtd'],
                (int)    $b['ntb_ytd'],
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
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER,
            'J' => NumberFormat::FORMAT_NUMBER,
            'K' => NumberFormat::FORMAT_NUMBER,
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
                $sheet->mergeCells("A1:K1");
                $sheet->getStyle('A1:K1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '002E4A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(26);
                $sheet->mergeCells('A2:K2');

                foreach ($this->boldRows as $r) {
                    $sheet->getStyle("A{$r}:K{$r}")->getFont()->setBold(true);
                }

                // Column header row
                $sheet->getStyle("A{$hdr}:K{$hdr}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3A5F']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("A{$hdr}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Deposits/Loans/NTB header tints
                $sheet->getStyle("C{$hdr}:E{$hdr}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']]]);
                $sheet->getStyle("F{$hdr}:H{$hdr}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '166534']]]);
                $sheet->getStyle("I{$hdr}:K{$hdr}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B45309']]]);

                // Branch data rows
                if ($this->lastBranchRow > $hdr) {
                    for ($row = $hdr + 1; $row <= $this->lastBranchRow; $row++) {
                        // Colour only the movement columns by sign — not the closing-balance columns (E, H)
                        foreach (['C', 'D', 'F', 'G'] as $col) {
                            $v = $sheet->getCell("{$col}{$row}")->getValue();
                            if (!is_numeric($v)) continue;
                            $vf = (float) $v;
                            if ($vf > 0)     $sheet->getStyle("{$col}{$row}")->getFont()->getColor()->setRGB('0B6E4F');
                            elseif ($vf < 0) $sheet->getStyle("{$col}{$row}")->getFont()->getColor()->setRGB('B00020');
                        }
                        $sheet->getStyle("C{$row}:E{$row}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']]]);
                        $sheet->getStyle("F{$row}:H{$row}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']]]);
                        $sheet->getStyle("I{$row}:K{$row}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']],
                            'font' => ['color' => ['rgb' => '92400E']],
                        ]);
                        $sheet->getStyle("E{$row}")->getFont()->getColor()->setRGB('374151');
                        $sheet->getStyle("H{$row}")->getFont()->getColor()->setRGB('374151');
                    }

                    // ALL row (last branch row) — bold + light grey
                    $sheet->getStyle("A{$this->lastBranchRow}:K{$this->lastBranchRow}")->applyFromArray([
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
                    $sheet->getStyle("A{$hdr}:K{$lastRow}")->getBorders()->getAllBorders()
                        ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('E2E8F0');
                }

                $sheet->freezePane('A' . ($hdr + 1));
            },
        ];
    }
}
