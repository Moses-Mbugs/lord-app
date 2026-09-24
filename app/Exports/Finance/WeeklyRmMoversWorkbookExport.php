<?php

declare(strict_types=1);

namespace App\Exports\Finance;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Weekly RM Movers workbook: RM Summary (Deposits WTD/MTD/YTD Δ + Closing Bal, Loans
 * WTD/MTD Δ + Closing Bal, NTB WTD/MTD/YTD) and, per RM, the week's top deposit customer
 * gainers/losers — same shape as WeeklyBranchMoversWorkbookExport, plus a Deposits YTD Δ
 * column (not present on the branch version) and a per-RM breakdown sheet instead of a
 * single flat top-movers list.
 */
class WeeklyRmMoversWorkbookExport implements WithMultipleSheets
{
    /**
     * @param array $periods ['week'|'mtd'|'ytd' => ['start','end','label']]
     * @param array $data    ['week'|'mtd'|'ytd' => ['summary','all']]
     * @param array<string,array{gainers: array, losers: array}> $groupedDrilldown from
     *        RmMoversService::drilldownGroupedByRmCodes() for the week period, keyed by rm_code
     */
    public function __construct(
        private readonly string $weekEnd,
        private readonly array  $periods,
        private readonly array  $data,
        private readonly array  $rmCodes,
        private readonly array  $rmNames,
        private readonly array  $groupedDrilldown
    ) {
    }

    public function sheets(): array
    {
        $weekPeriod = $this->periods['week'] ?? ['start' => $this->weekEnd, 'end' => $this->weekEnd];

        return [
            new WeeklyRmSummarySheet($this->weekEnd, $this->periods, $this->data),
            new RmDepositMoversSheet(
                $weekPeriod['start'],
                $weekPeriod['end'],
                $this->rmCodes,
                $this->rmNames,
                $this->groupedDrilldown
            ),
        ];
    }
}

/**
 * SHEET 1: Rank (by Deposits WTD Δ, best first), RM Code, RM Name,
 * Deposits (WTD Δ/MTD Δ/YTD Δ/Closing Bal), Loans (WTD Δ/MTD Δ/Closing Bal),
 * NTB (WTD/MTD/YTD), plus a Total row (unranked).
 */
class WeeklyRmSummarySheet implements FromArray, WithTitle, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    private const NUM_COLS = 13;

    private array $boldRows   = [];
    private int   $headerRow  = 0;
    private int   $lastRmRow  = 0;

    /** @param array $periods ['week'|'mtd'|'ytd' => ['start','end','label']] */
    /** @param array $data    ['week'|'mtd'|'ytd' => ['summary','all']] */
    public function __construct(
        private readonly string $weekEnd,
        private readonly array  $periods,
        private readonly array  $data
    ) {
    }

    public function title(): string
    {
        return 'Weekly RM Summary';
    }

    public function array(): array
    {
        $weekPeriod = $this->periods['week'] ?? [];
        $mtdPeriod  = $this->periods['mtd']  ?? [];
        $ytdPeriod  = $this->periods['ytd']  ?? [];

        $weekData = $this->data['week'] ?? ['summary' => collect(), 'all' => null];
        $mtdData  = $this->data['mtd']  ?? ['summary' => collect(), 'all' => null];
        $ytdData  = $this->data['ytd']  ?? ['summary' => collect(), 'all' => null];

        $map = [];

        foreach (['week' => $weekData, 'mtd' => $mtdData, 'ytd' => $ytdData] as $key => $periodData) {
            foreach (collect($periodData['summary'] ?? []) as $r) {
                $code = (string) ($r->rm_code ?? '');
                if ($code === '') continue;
                if (!isset($map[$code])) {
                    $map[$code] = [
                        'code' => $code, 'name' => (string) ($r->rm_name ?? $code),
                        'dep_week' => 0, 'dep_mtd' => 0, 'dep_ytd' => 0, 'dep_balance' => 0,
                        'loan_week' => 0, 'loan_mtd' => 0, 'loan_balance' => 0,
                        'ntb_week' => 0, 'ntb_mtd' => 0, 'ntb_ytd' => 0,
                    ];
                }
                $map[$code]['name']       = (string) ($r->rm_name ?? $map[$code]['name']);
                $map[$code]["ntb_{$key}"] = (int) ($r->ntb_count ?? 0);
                $map[$code]["dep_{$key}"] = (float) ($r->movement ?? 0);
                if ($key !== 'ytd') {
                    $map[$code]["loan_{$key}"] = (float) ($r->loan_movement ?? 0);
                }
                if ($key === 'week') {
                    $map[$code]['dep_balance']  = (float) ($r->end_balance ?? 0);
                    $map[$code]['loan_balance'] = (float) ($r->loan_close  ?? 0);
                }
            }
        }

        // Rank by WTD deposit movement (best performer first).
        uasort($map, fn ($a, $b) => $b['dep_week'] <=> $a['dep_week']);

        $rows   = [];
        $rowNum = 0;

        $rows[] = array_pad(['ECOBANK KENYA — WEEKLY RM MOVERS'], self::NUM_COLS, '');
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

        $this->headerRow = ++$rowNum;
        $rows[] = [
            'Rank', 'RM Code', 'RM Name',
            'Deposits WTD Δ', 'Deposits MTD Δ', 'Deposits YTD Δ', 'Deposits Closing Bal',
            'Loans WTD Δ', 'Loans MTD Δ', 'Loans Closing Bal',
            'NTB WTD', 'NTB MTD', 'NTB YTD',
        ];
        $this->boldRows[] = $this->headerRow;

        $rank = 0;
        foreach ($map as $r) {
            ++$rowNum;
            ++$rank;
            $rows[] = [
                $rank,
                (string) $r['code'],
                (string) $r['name'],
                (float)  $r['dep_week'],
                (float)  $r['dep_mtd'],
                (float)  $r['dep_ytd'],
                (float)  $r['dep_balance'],
                (float)  $r['loan_week'],
                (float)  $r['loan_mtd'],
                (float)  $r['loan_balance'],
                (int)    $r['ntb_week'],
                (int)    $r['ntb_mtd'],
                (int)    $r['ntb_ytd'],
            ];
        }
        $this->lastRmRow = $rowNum;

        // Total row (excluded from ranking)
        $weekAll = $weekData['all'] ?? null;
        $mtdAll  = $mtdData['all']  ?? null;
        $ytdAll  = $ytdData['all']  ?? null;
        ++$rowNum;
        $rows[] = [
            '', 'ALL', 'Total',
            (float) ($weekAll->movement    ?? 0),
            (float) ($mtdAll->movement     ?? 0),
            (float) ($ytdAll->movement     ?? 0),
            (float) ($weekAll->end_balance ?? 0),
            (float) ($weekAll->loan_movement ?? 0),
            (float) ($mtdAll->loan_movement  ?? 0),
            (float) ($weekAll->loan_close    ?? 0),
            (int)   ($weekAll->ntb_count      ?? 0),
            (int)   ($mtdAll->ntb_count       ?? 0),
            (int)   ($ytdAll->ntb_count       ?? 0),
        ];
        $this->boldRows[] = $rowNum;

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_NUMBER,
            'L' => NumberFormat::FORMAT_NUMBER,
            'M' => NumberFormat::FORMAT_NUMBER,
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

                $sheet->mergeCells('A1:M1');
                $sheet->getStyle('A1:M1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '002E4A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(26);
                $sheet->mergeCells('A2:M2');

                foreach ($this->boldRows as $r) {
                    $sheet->getStyle("A{$r}:M{$r}")->getFont()->setBold(true);
                }

                $sheet->getStyle("A{$hdr}:M{$hdr}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3A5F']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("A{$hdr}:C{$hdr}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C{$hdr}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $sheet->getStyle("D{$hdr}:G{$hdr}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']]]);
                $sheet->getStyle("H{$hdr}:J{$hdr}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '166534']]]);
                $sheet->getStyle("K{$hdr}:M{$hdr}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B45309']]]);

                if ($this->lastRmRow > $hdr) {
                    for ($row = $hdr + 1; $row <= $this->lastRmRow; $row++) {
                        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("A{$row}")->getFont()->setBold(true);

                        foreach (['D', 'E', 'F', 'H', 'I'] as $col) {
                            $v = $sheet->getCell("{$col}{$row}")->getValue();
                            if (!is_numeric($v)) continue;
                            $vf = (float) $v;
                            if ($vf > 0)     $sheet->getStyle("{$col}{$row}")->getFont()->getColor()->setRGB('0B6E4F');
                            elseif ($vf < 0) $sheet->getStyle("{$col}{$row}")->getFont()->getColor()->setRGB('B00020');
                        }
                        $sheet->getStyle("D{$row}:G{$row}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']]]);
                        $sheet->getStyle("H{$row}:J{$row}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']]]);
                        $sheet->getStyle("K{$row}:M{$row}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']],
                            'font' => ['color' => ['rgb' => '92400E']],
                        ]);
                        $sheet->getStyle("G{$row}")->getFont()->getColor()->setRGB('374151');
                        $sheet->getStyle("J{$row}")->getFont()->getColor()->setRGB('374151');
                    }
                }

                // Total row (last data row)
                $sheet->getStyle("A{$lastRow}:M{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                ]);

                if ($lastRow > $hdr) {
                    $sheet->getStyle("A{$hdr}:M{$lastRow}")->getBorders()->getAllBorders()
                        ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('E2E8F0');
                }

                $sheet->freezePane('A' . ($hdr + 1));
            },
        ];
    }
}
