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
 * Weekly RM Loan Movers workbook: RM Loan Summary (grouped by Job Unit segment, ranked
 * within each segment by Loans WTD Δ, with a subtotal row per segment and a grand Total
 * row) and, per RM (grouped in segment order), the week's top loan account gainers/losers.
 * Scoped to the performing, non-Corporate, non-staff loan book.
 */
class WeeklyRmLoanMoversWorkbookExport implements WithMultipleSheets
{
    /**
     * @param array $periods ['week'|'mtd'|'ytd' => ['start','end','label']]
     * @param array<string, array> $segmentsData keyed by segment name, each shaped like
     *        ['week'|'mtd'|'ytd' => ['period','summary','all']]
     * @param array<string, object> $grandData 'week'|'mtd'|'ytd' => 'all'-shaped object,
     *        summed across every segment
     * @param array $rmCodes RM codes in segment-grouped order
     * @param array<string,array{gainers: array, losers: array}> $groupedDrilldown from
     *        RmLoanMoversService::accountMoversGroupedByRmCodes() for the week period
     */
    public function __construct(
        private readonly string $weekEnd,
        private readonly array  $periods,
        private readonly array  $segmentsData,
        private readonly array  $grandData,
        private readonly array  $rmCodes,
        private readonly array  $rmNames,
        private readonly array  $groupedDrilldown
    ) {
    }

    public function sheets(): array
    {
        $weekPeriod = $this->periods['week'] ?? ['start' => $this->weekEnd, 'end' => $this->weekEnd];

        return [
            new WeeklyRmLoanSummarySheet($this->weekEnd, $this->periods, $this->segmentsData, $this->grandData),
            new LoanAccountMoversByRmSheet(
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
 * SHEET 1: Segment, Rank (by Loans WTD Δ, best first within each segment), RM Code,
 * RM Name, Accounts, Customers, Loans (WTD Δ/MTD Δ/YTD Δ/Closing Bal), with a subtotal
 * row per segment and a grand Total row.
 */
class WeeklyRmLoanSummarySheet implements FromArray, WithTitle, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    private const NUM_COLS = 10;

    private array $boldRows  = [];
    private int   $headerRow = 0;
    private int   $lastRmRow = 0;

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
        return 'Weekly RM Loan Summary';
    }

    public function array(): array
    {
        $weekPeriod = $this->periods['week'] ?? [];
        $mtdPeriod  = $this->periods['mtd']  ?? [];
        $ytdPeriod  = $this->periods['ytd']  ?? [];

        $rows   = [];
        $rowNum = 0;

        $rows[] = array_pad(['ECOBANK KENYA — WEEKLY RM LOAN MOVERS'], self::NUM_COLS, '');
        $this->boldRows[] = ++$rowNum;

        $rows[] = array_pad(["Week ending: {$this->weekEnd}  |  Performing book only, Corporate and Staff loans excluded"], self::NUM_COLS, '');
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
            'Segment', 'Rank', 'RM Code', 'RM Name', 'Loan Accounts', 'Customers',
            'Loans WTD Δ', 'Loans MTD Δ', 'Loans YTD Δ', 'Loans Closing Bal',
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
                    (int) $r['accounts'], (int) $r['customers'],
                    (float) $r['loan_week'], (float) $r['loan_mtd'], (float) $r['loan_ytd'], (float) $r['loan_balance'],
                ];
            }

            $weekAll = $data['week']['all'] ?? null;
            $mtdAll  = $data['mtd']['all']  ?? null;
            $ytdAll  = $data['ytd']['all']  ?? null;
            ++$rowNum;
            $rows[] = [
                $segment, '', '', 'TOTAL',
                (int) ($weekAll->account_count  ?? 0),
                (int) ($weekAll->customer_count ?? 0),
                (float) ($weekAll->loan_movement ?? 0),
                (float) ($mtdAll->loan_movement  ?? 0),
                (float) ($ytdAll->loan_movement  ?? 0),
                (float) ($weekAll->loan_close    ?? 0),
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
            (int) ($weekAll->account_count  ?? 0),
            (int) ($weekAll->customer_count ?? 0),
            (float) ($weekAll->loan_movement ?? 0),
            (float) ($mtdAll->loan_movement  ?? 0),
            (float) ($ytdAll->loan_movement  ?? 0),
            (float) ($weekAll->loan_close    ?? 0),
        ];
        ++$rowNum;
        $this->boldRows[] = $rowNum;

        return $rows;
    }

    /** Builds this segment's rm_code => fields map, ranked by WTD loan movement. */
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
                        'accounts' => 0, 'customers' => 0,
                        'loan_week' => 0, 'loan_mtd' => 0, 'loan_ytd' => 0, 'loan_balance' => 0,
                    ];
                }
                $map[$code]['name']        = (string) ($r->rm_name ?? $map[$code]['name']);
                $map[$code]["loan_{$key}"] = (float) ($r->loan_movement ?? 0);
                if ($key === 'week') {
                    $map[$code]['accounts']     = (int) ($r->account_count  ?? 0);
                    $map[$code]['customers']    = (int) ($r->customer_count ?? 0);
                    $map[$code]['loan_balance'] = (float) ($r->loan_close   ?? 0);
                }
            }
        }

        uasort($map, fn ($a, $b) => $b['loan_week'] <=> $a['loan_week']);

        return $map;
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_NUMBER,
            'F' => NumberFormat::FORMAT_NUMBER,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
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

                $sheet->mergeCells('A1:J1');
                $sheet->getStyle('A1:J1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '002E4A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(26);
                $sheet->mergeCells('A2:J2');

                foreach ($this->boldRows as $r) {
                    $sheet->getStyle("A{$r}:J{$r}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                    ]);
                }

                $sheet->getStyle("A{$hdr}:J{$hdr}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '166534']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("D{$hdr}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                if ($this->lastRmRow > $hdr) {
                    for ($row = $hdr + 1; $row <= $this->lastRmRow; $row++) {
                        if (in_array($row, $this->boldRows, true)) continue; // subtotal row — leave its own styling

                        $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("B{$row}")->getFont()->setBold(true);

                        foreach (['G', 'H', 'I'] as $col) {
                            $v = $sheet->getCell("{$col}{$row}")->getValue();
                            if (!is_numeric($v)) continue;
                            $vf = (float) $v;
                            if ($vf > 0)     $sheet->getStyle("{$col}{$row}")->getFont()->getColor()->setRGB('0B6E4F');
                            elseif ($vf < 0) $sheet->getStyle("{$col}{$row}")->getFont()->getColor()->setRGB('B00020');
                        }
                        $sheet->getStyle("G{$row}:J{$row}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']]]);
                        $sheet->getStyle("J{$row}")->getFont()->getColor()->setRGB('374151');
                    }
                }

                $sheet->getStyle("A{$lastRow}:J{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'CBD5E1']],
                ]);

                if ($lastRow > $hdr) {
                    $sheet->getStyle("A{$hdr}:J{$lastRow}")->getBorders()->getAllBorders()
                        ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('E2E8F0');
                }

                $sheet->freezePane('A' . ($hdr + 1));
            },
        ];
    }
}
