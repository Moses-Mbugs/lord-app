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
     */
    public function __construct(
        private readonly string $weekEnd,
        private readonly array  $periods,
        private readonly array  $segmentsData,
        private readonly array  $grandData,
        private readonly array  $rmCodes,
        private readonly array  $rmNames,
        private readonly array  $groupedDrilldown,
        private readonly array  $groupedLoanDrilldown
    ) {
    }

    public function sheets(): array
    {
        $weekPeriod = $this->periods['week'] ?? ['start' => $this->weekEnd, 'end' => $this->weekEnd];

        return [
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
