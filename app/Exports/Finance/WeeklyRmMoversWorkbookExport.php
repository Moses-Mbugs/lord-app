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
 * Weekly RM Movers workbook: RM Summary (grouped by Job Unit segment, ranked within each
 * segment by Deposits WTD Δ, with a subtotal row per segment and a grand Total row), per RM
 * (grouped in segment order) the week's top deposit customer gainers/losers, and per RM the
 * week's top loan account gainers/losers.
 */
class WeeklyRmMoversWorkbookExport implements WithMultipleSheets
{
    /**
     * @param array $periods ['week'|'mtd'|'ytd' => ['start','end','label']]
     * @param array<string, array> $segmentsData keyed by segment name, each shaped like
     *        ['week'|'mtd'|'ytd' => ['period','summary','all']]
     * @param array<string, object> $grandData 'week'|'mtd'|'ytd' => 'all'-shaped object,
     *        summed across every segment
     * @param array $rmCodes RM codes in segment-grouped order (so the movers sheets group by segment)
     * @param array<string,array{gainers: array, losers: array}> $groupedDrilldown from
     *        RmMoversService::drilldownGroupedByRmCodes() for the week period, keyed by rm_code
     * @param array<string,array{gainers: array, losers: array}> $groupedLoanDrilldown from
     *        RmLoanMoversService::accountMoversGroupedByRmCodes() for the week period, keyed by rm_code
     * @param array<string, array{rows: \Illuminate\Support\Collection, totals: object}> $budgetData
     *        keyed by segment name — deposit/NTB actual vs FY target; empty if no RM has a
     *        target recorded for $targetYear (in which case no Budget sheet is added)
     * @param object|null $budgetGrand same shape as a segment's totals, summed across all segments
     */
    public function __construct(
        private readonly string $weekEnd,
        private readonly array  $periods,
        private readonly array  $segmentsData,
        private readonly array  $grandData,
        private readonly array  $rmCodes,
        private readonly array  $rmNames,
        private readonly array  $groupedDrilldown,
        private readonly array  $groupedLoanDrilldown,
        private readonly int    $targetYear = 0,
        private readonly array  $budgetData = [],
        private readonly ?object $budgetGrand = null
    ) {
    }

    public function sheets(): array
    {
        $weekPeriod = $this->periods['week'] ?? ['start' => $this->weekEnd, 'end' => $this->weekEnd];

        $sheets = [
            new WeeklyRmSummarySheet($this->weekEnd, $this->periods, $this->segmentsData, $this->grandData),
            new RmDepositMoversSheet(
                $weekPeriod['start'],
                $weekPeriod['end'],
                $this->rmCodes,
                $this->rmNames,
                $this->groupedDrilldown
            ),
            new LoanAccountMoversByRmSheet(
                $weekPeriod['start'],
                $weekPeriod['end'],
                $this->rmCodes,
                $this->rmNames,
                $this->groupedLoanDrilldown
            ),
        ];

        if (!empty($this->budgetData) && $this->budgetGrand) {
            $sheets[] = new WeeklyRmBudgetSheet($this->targetYear, $this->budgetData, $this->budgetGrand);
        }

        return $sheets;
    }
}

/**
 * SHEET 1: Segment, Rank (by Deposits WTD Δ, best first within each segment), RM Code,
 * RM Name, Deposits (WTD Δ/MTD Δ/YTD Δ/Closing Bal), Loans (WTD Δ/MTD Δ/Closing Bal),
 * NTB (WTD/MTD/YTD), with a subtotal row per segment and a grand Total row.
 */
class WeeklyRmSummarySheet implements FromArray, WithTitle, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    private const NUM_COLS = 14;

    private array $boldRows   = [];
    private int   $headerRow  = 0;
    private int   $lastRmRow  = 0;

    /** @var array<string, array{0: int, 1: int}> segment => [firstRow, lastRow] (inclusive, incl. its TOTAL row) */
    private array $segmentRowRanges = [];

    /**
     * @param array $periods ['week'|'mtd'|'ytd' => ['start','end','label']]
     * @param array<string, array> $segmentsData keyed by segment name
     * @param array<string, object> $grandData
     */
    public function __construct(
        private readonly string $weekEnd,
        private readonly array  $periods,
        private readonly array  $segmentsData,
        private readonly array  $grandData
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
            'Segment', 'Rank', 'RM Code', 'RM Name',
            'Deposits WTD Δ', 'Deposits MTD Δ', 'Deposits YTD Δ', 'Deposits Closing Bal',
            'Loans WTD Δ', 'Loans MTD Δ', 'Loans Closing Bal',
            'NTB WTD', 'NTB MTD', 'NTB YTD',
        ];
        $this->boldRows[] = $this->headerRow;

        foreach ($this->segmentsData as $segment => $data) {
            $map = $this->buildMap($data);
            $segmentFirstRow = $rowNum + 1;

            $rank = 0;
            foreach ($map as $r) {
                ++$rowNum;
                ++$rank;
                $rows[] = [
                    $segment, $rank, (string) $r['code'], (string) $r['name'],
                    (float) $r['dep_week'], (float) $r['dep_mtd'], (float) $r['dep_ytd'], (float) $r['dep_balance'],
                    (float) $r['loan_week'], (float) $r['loan_mtd'], (float) $r['loan_balance'],
                    (int) $r['ntb_week'], (int) $r['ntb_mtd'], (int) $r['ntb_ytd'],
                ];
            }

            $weekAll = $data['week']['all'] ?? null;
            $mtdAll  = $data['mtd']['all']  ?? null;
            $ytdAll  = $data['ytd']['all']  ?? null;
            ++$rowNum;
            $rows[] = [
                $segment, '', '', 'TOTAL',
                (float) ($weekAll->movement      ?? 0),
                (float) ($mtdAll->movement       ?? 0),
                (float) ($ytdAll->movement       ?? 0),
                (float) ($weekAll->end_balance   ?? 0),
                (float) ($weekAll->loan_movement ?? 0),
                (float) ($mtdAll->loan_movement  ?? 0),
                (float) ($weekAll->loan_close    ?? 0),
                (int)   ($weekAll->ntb_count     ?? 0),
                (int)   ($mtdAll->ntb_count      ?? 0),
                (int)   ($ytdAll->ntb_count      ?? 0),
            ];
            $this->boldRows[] = $rowNum;
            $this->segmentRowRanges[$segment] = [$segmentFirstRow, $rowNum];
        }
        $this->lastRmRow = $rowNum;

        $weekAll = $this->grandData['week'] ?? null;
        $mtdAll  = $this->grandData['mtd']  ?? null;
        $ytdAll  = $this->grandData['ytd']  ?? null;
        ++$rowNum;
        $rows[] = [
            'GRAND TOTAL', '', '', '',
            (float) ($weekAll->movement      ?? 0),
            (float) ($mtdAll->movement       ?? 0),
            (float) ($ytdAll->movement       ?? 0),
            (float) ($weekAll->end_balance   ?? 0),
            (float) ($weekAll->loan_movement ?? 0),
            (float) ($mtdAll->loan_movement  ?? 0),
            (float) ($weekAll->loan_close    ?? 0),
            (int)   ($weekAll->ntb_count     ?? 0),
            (int)   ($mtdAll->ntb_count      ?? 0),
            (int)   ($ytdAll->ntb_count      ?? 0),
        ];
        ++$rowNum;
        $this->boldRows[] = $rowNum;

        return $rows;
    }

    /** Builds this segment's rm_code => fields map, ranked by WTD deposit movement. */
    private function buildMap(array $data): array
    {
        $weekData = $data['week'] ?? ['summary' => collect()];
        $mtdData  = $data['mtd']  ?? ['summary' => collect()];
        $ytdData  = $data['ytd']  ?? ['summary' => collect()];

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

        uasort($map, fn ($a, $b) => $b['dep_week'] <=> $a['dep_week']);

        return $map;
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'L' => NumberFormat::FORMAT_NUMBER,
            'M' => NumberFormat::FORMAT_NUMBER,
            'N' => NumberFormat::FORMAT_NUMBER,
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

                $sheet->mergeCells('A1:N1');
                $sheet->getStyle('A1:N1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '002E4A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(26);
                $sheet->mergeCells('A2:N2');

                foreach ($this->boldRows as $r) {
                    $sheet->getStyle("A{$r}:N{$r}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                    ]);
                }
                // Header row keeps its own stronger styling (applied after the generic bold pass above).
                $sheet->getStyle("A{$hdr}:N{$hdr}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3A5F']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("D{$hdr}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $sheet->getStyle("E{$hdr}:H{$hdr}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']]]);
                $sheet->getStyle("I{$hdr}:K{$hdr}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '166534']]]);
                $sheet->getStyle("L{$hdr}:N{$hdr}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B45309']]]);

                if ($this->lastRmRow > $hdr) {
                    for ($row = $hdr + 1; $row <= $this->lastRmRow; $row++) {
                        if (in_array($row, $this->boldRows, true)) continue; // subtotal row — leave its own styling

                        $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("B{$row}")->getFont()->setBold(true);

                        foreach (['E', 'F', 'G', 'I', 'J'] as $col) {
                            $v = $sheet->getCell("{$col}{$row}")->getValue();
                            if (!is_numeric($v)) continue;
                            $vf = (float) $v;
                            if ($vf > 0)     $sheet->getStyle("{$col}{$row}")->getFont()->getColor()->setRGB('0B6E4F');
                            elseif ($vf < 0) $sheet->getStyle("{$col}{$row}")->getFont()->getColor()->setRGB('B00020');
                        }
                        $sheet->getStyle("E{$row}:H{$row}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']]]);
                        $sheet->getStyle("I{$row}:K{$row}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']]]);
                        $sheet->getStyle("L{$row}:N{$row}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']],
                            'font' => ['color' => ['rgb' => '92400E']],
                        ]);
                        $sheet->getStyle("H{$row}")->getFont()->getColor()->setRGB('374151');
                        $sheet->getStyle("K{$row}")->getFont()->getColor()->setRGB('374151');
                    }
                }

                // Color-code the Segment column per segment, with a matching left border
                // stripe down the whole row so each segment (incl. its TOTAL row) reads as
                // its own color band.
                foreach ($this->segmentRowRanges as $segment => [$first, $last]) {
                    $color = \App\Services\Reports\RmPortfolioService::segmentColor($segment);

                    $sheet->getStyle("A{$first}:A{$last}")->applyFromArray([
                        'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color['fill']]],
                        'font'    => ['color' => ['rgb' => $color['fillText']]],
                        'borders' => ['left' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => $color['border']]]],
                    ]);
                }

                // Grand total row (last row)
                $sheet->getStyle("A{$lastRow}:N{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'CBD5E1']],
                ]);

                if ($lastRow > $hdr) {
                    $sheet->getStyle("A{$hdr}:N{$lastRow}")->getBorders()->getAllBorders()
                        ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('E2E8F0');
                }

                $sheet->freezePane('A' . ($hdr + 1));
            },
        ];
    }
}

/**
 * SHEET 4 (when targets exist): Budget vs Actual for $targetYear — deposit closing balance
 * and YTD NTB count vs each RM's FY target, grouped by segment with a subtotal row per
 * segment and a grand Total row, color-coded the same way as the other summary sheets.
 */
class WeeklyRmBudgetSheet implements FromArray, WithTitle, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    private const NUM_COLS = 9;

    private array $boldRows = [];

    /** @var array<string, array{0: int, 1: int}> segment => [firstRow, lastRow] (inclusive, incl. its TOTAL row) */
    private array $segmentRowRanges = [];

    /** @param array<string, array{rows: \Illuminate\Support\Collection, totals: object}> $budgetData */
    public function __construct(
        private readonly int $targetYear,
        private readonly array $budgetData,
        private readonly object $budgetGrand
    ) {
    }

    public function title(): string
    {
        return 'Budget vs Actual';
    }

    public function array(): array
    {
        $rows   = [];
        $rowNum = 0;

        $rows[] = array_pad(["ECOBANK KENYA — RM BUDGET VS ACTUAL, FY{$this->targetYear}"], self::NUM_COLS, '');
        $this->boldRows[] = ++$rowNum;

        $rows[] = array_fill(0, self::NUM_COLS, '');
        ++$rowNum;

        $headerRow = ++$rowNum;
        $rows[] = [
            'Segment', 'RM Code', 'RM Name',
            'Deposit Target', 'Deposit Actual', 'Deposit %',
            'NTB Target', 'NTB Actual', 'NTB %',
        ];
        $this->boldRows[] = $headerRow;

        foreach ($this->budgetData as $segment => $bd) {
            $segmentFirstRow = $rowNum + 1;

            foreach ($bd['rows'] as $r) {
                ++$rowNum;
                $rows[] = [
                    $segment, (string) $r->rm_code, (string) $r->rm_name,
                    (float) $r->deposit_target, (float) $r->deposit_actual,
                    $r->deposit_pct === null ? '' : ((float) $r->deposit_pct / 100),
                    (int) $r->ntb_target, (int) $r->ntb_actual,
                    $r->ntb_pct === null ? '' : ((float) $r->ntb_pct / 100),
                ];
            }

            $t = $bd['totals'];
            ++$rowNum;
            $rows[] = [
                $segment . ' TOTAL', '', '',
                (float) $t->deposit_target, (float) $t->deposit_actual,
                $t->deposit_pct === null ? '' : ((float) $t->deposit_pct / 100),
                (int) $t->ntb_target, (int) $t->ntb_actual,
                $t->ntb_pct === null ? '' : ((float) $t->ntb_pct / 100),
            ];
            $this->boldRows[] = $rowNum;
            $this->segmentRowRanges[$segment] = [$segmentFirstRow, $rowNum];
        }

        $g = $this->budgetGrand;
        ++$rowNum;
        $rows[] = [
            'GRAND TOTAL', '', '',
            (float) $g->deposit_target, (float) $g->deposit_actual,
            $g->deposit_pct === null ? '' : ((float) $g->deposit_pct / 100),
            (int) $g->ntb_target, (int) $g->ntb_actual,
            $g->ntb_pct === null ? '' : ((float) $g->ntb_pct / 100),
        ];
        $this->boldRows[] = $rowNum;

        $this->headerRowCache = $headerRow;

        return $rows;
    }

    private int $headerRowCache = 0;

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => '0%',
            'G' => NumberFormat::FORMAT_NUMBER,
            'H' => NumberFormat::FORMAT_NUMBER,
            'I' => '0%',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                /** @var Worksheet $sheet */
                $sheet   = $event->sheet->getDelegate();
                $hdr     = $this->headerRowCache;
                $lastRow = $sheet->getHighestRow();

                $sheet->mergeCells('A1:I1');
                $sheet->getStyle('A1:I1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '002E4A']],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(26);

                foreach ($this->boldRows as $r) {
                    $sheet->getStyle("A{$r}:I{$r}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                    ]);
                }

                $sheet->getStyle("A{$hdr}:I{$hdr}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3A5F']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("C{$hdr}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $sheet->getStyle("D{$hdr}:F{$hdr}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']]]);
                $sheet->getStyle("G{$hdr}:I{$hdr}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B45309']]]);

                // Color-code achievement %: green >=100%, amber 75-99%, red <75%.
                foreach (['F', 'I'] as $col) {
                    for ($row = $hdr + 1; $row <= $lastRow; $row++) {
                        $v = $sheet->getCell("{$col}{$row}")->getValue();
                        if (!is_numeric($v)) continue;
                        $pct = (float) $v * 100;
                        $rgb = $pct >= 100 ? '166534' : ($pct >= 75 ? '92400E' : '991B1B');
                        $sheet->getStyle("{$col}{$row}")->getFont()->applyFromArray(['bold' => true, 'color' => ['rgb' => $rgb]]);
                    }
                }

                // Color-code the Segment column per segment, with a matching left border stripe.
                foreach ($this->segmentRowRanges as $segment => [$first, $last]) {
                    $color = \App\Services\Reports\RmPortfolioService::segmentColor($segment);

                    $sheet->getStyle("A{$first}:A{$last}")->applyFromArray([
                        'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color['fill']]],
                        'font'    => ['color' => ['rgb' => $color['fillText']]],
                        'borders' => ['left' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => $color['border']]]],
                    ]);
                }

                $sheet->getStyle("A{$lastRow}:I{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'CBD5E1']],
                ]);

                if ($lastRow > $hdr) {
                    $sheet->getStyle("A{$hdr}:I{$lastRow}")->getBorders()->getAllBorders()
                        ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('E2E8F0');
                }

                $sheet->freezePane('A' . ($hdr + 1));
            },
        ];
    }
}
