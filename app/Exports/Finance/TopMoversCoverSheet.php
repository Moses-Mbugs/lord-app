<?php

declare(strict_types=1);

namespace App\Exports\Finance;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TopMoversCoverSheet implements FromArray, WithTitle, WithColumnWidths, WithEvents
{
    private array $segmentRows  = [];
    private array $kpiRows      = [];
    private array $boldRows     = [];
    private array $mergeRows    = [];
    private int   $headerRow    = 0;

    private const SEGMENT_COLOURS = [
        'CB'  => ['fill' => 'DBEAFE', 'font' => '1E3A8A'],
        'CM'  => ['fill' => 'EDE9FE', 'font' => '4C1D95'],
        'CS'  => ['fill' => 'FEF3C7', 'font' => '78350F'],
        'OT'  => ['fill' => 'F1F5F9', 'font' => '334155'],
        'ALL' => ['fill' => 'E2E8F0', 'font' => '0F172A'],
    ];

    public function __construct(
        private readonly array      $grouped,
        private readonly string     $start,
        private readonly string     $end,
        private readonly Collection $segments
    ) {}

    public function title(): string { return 'Summary'; }

    public function columnWidths(): array
    {
        return ['A' => 32, 'B' => 22, 'C' => 22, 'D' => 22, 'E' => 22];
    }

    public function array(): array
    {
        $rows   = [];
        $rowNum = 0;

        // ── Brand / title ─────────────────────────────────────────
        $rows[] = ['ECOBANK KENYA — DAILY DEPOSITS MOVEMENT REPORT', '', '', '', ''];
        $this->mergeRows[] = ++$rowNum;
        $this->boldRows[]  = $rowNum;

        $rows[] = ['', '', '', '', ''];
        ++$rowNum;

        $rows[] = ['Reporting Period', "{$this->start}  →  {$this->end}", '', '', ''];
        $this->boldRows[] = ++$rowNum;

        $rows[] = ['Generated', now()->timezone(config('app.timezone', 'Africa/Nairobi'))->format('d M Y  H:i'), '', '', ''];
        ++$rowNum;

        $rows[] = ['', '', '', '', ''];
        ++$rowNum;

        // ── KPI summary row ───────────────────────────────────────
        $gainRows  = ($this->grouped['CIF_ONLY']['GAIN'] ?? collect())->values();
        $lossRows  = ($this->grouped['CIF_ONLY']['LOSS'] ?? collect())->values();
        $allRows   = $gainRows->concat($lossRows);

        $totalStart    = (float) $allRows->sum('start_balance');
        $totalEnd      = (float) $allRows->sum('end_balance');
        $totalMovement = (float) $allRows->sum('movement');
        $totalLcy      = (float) $allRows->sum('lcy_movement');
        $totalFcy      = (float) $allRows->sum('fcy_movement');

        $kpiHeaderRow = ++$rowNum;
        $rows[] = ['KPI', 'Total Deposits (Start)', 'Total Deposits (End)', 'Net Movement', 'Top Gainers / Losers'];
        $this->boldRows[] = $kpiHeaderRow;
        $this->kpiRows[]  = $kpiHeaderRow;

        $kpiDataRow = ++$rowNum;
        $rows[] = [
            'Aggregate',
            $totalStart,
            $totalEnd,
            $totalMovement,
            count($gainRows) . ' gainers  /  ' . count($lossRows) . ' losers shown',
        ];
        $this->boldRows[] = $kpiDataRow;
        $this->kpiRows[]  = $kpiDataRow;

        $rows[] = ['', '', '', '', ''];
        ++$rowNum;

        // ── LCY / FCY totals ──────────────────────────────────────
        ++$rowNum;
        $rows[] = ['Currency Breakdown', 'LCY Net Movement', 'FCY Net Movement (KES Eqv)', '', ''];
        $this->mergeRows[] = $rowNum; // not full merge — just first cell
        $this->boldRows[]  = $rowNum;

        ++$rowNum;
        $rows[] = ['Combined (Gainers + Losers)', $totalLcy, $totalFcy, '', ''];

        $rows[] = ['', '', '', '', ''];
        ++$rowNum;

        // ── Segment overview ──────────────────────────────────────
        if ($this->segments->isNotEmpty()) {
            $segHeaderRow = ++$rowNum;
            $rows[] = ['Segment', 'Opening Balance', 'Closing Balance', 'Movement', 'Direction'];
            $this->boldRows[]   = $segHeaderRow;
            $this->headerRow    = $segHeaderRow;

            foreach ($this->segments as $s) {
                $segDataRow = ++$rowNum;
                $code = strtoupper((string) ($s->segment_code ?? 'OT'));
                $rows[] = [
                    (string) ($s->segment_name ?? $code),
                    (float)  ($s->start_balance ?? 0),
                    (float)  ($s->end_balance   ?? 0),
                    (float)  ($s->movement      ?? 0),
                    (float) ($s->movement ?? 0) >= 0 ? 'GAIN' : 'LOSS',
                ];
                $this->segmentRows[$segDataRow] = $code;
                $this->boldRows[] = $segDataRow;
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

                // Title row — large, navy on dark background
                $sheet->getStyle('A1:E1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '002E4A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->mergeCells('A1:E1');
                $sheet->getRowDimension(1)->setRowHeight(30);

                // Meta rows (3-4)
                foreach ([3, 4] as $r) {
                    $sheet->getStyle("A{$r}")->getFont()->setBold(true);
                    $sheet->getStyle("A{$r}")->getFont()->getColor()->setRGB('1F3A5F');
                }

                // KPI rows
                foreach ($this->kpiRows as $r) {
                    $sheet->getStyle("A{$r}:E{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '005B82']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("B{$r}:D{$r}")
                        ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    $sheet->getStyle("B{$r}:D{$r}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                // Colour net movement cell
                foreach ($this->kpiRows as $r) {
                    $v = $sheet->getCell("D{$r}")->getValue();
                    if (!is_numeric($v)) continue;
                    $vf = (float) $v;
                    if ($vf > 0)      $sheet->getStyle("D{$r}")->getFont()->getColor()->setRGB('BED600');
                    elseif ($vf < 0)  $sheet->getStyle("D{$r}")->getFont()->getColor()->setRGB('FF6B6B');
                }

                // Segment overview header
                if ($this->headerRow > 0) {
                    $hdr = $this->headerRow;
                    $sheet->getStyle("A{$hdr}:E{$hdr}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3A5F']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getStyle("A{$hdr}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }

                // Segment data rows — colour by segment code
                foreach ($this->segmentRows as $r => $code) {
                    $colours = self::SEGMENT_COLOURS[$code] ?? self::SEGMENT_COLOURS['OT'];
                    $sheet->getStyle("A{$r}:E{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => $colours['font']]],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colours['fill']]],
                    ]);
                    $sheet->getStyle("B{$r}:D{$r}")
                        ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    $sheet->getStyle("B{$r}:D{$r}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    // Colour movement column D
                    $v = $sheet->getCell("D{$r}")->getValue();
                    if (is_numeric($v)) {
                        $vf = (float) $v;
                        if ($vf > 0)      $sheet->getStyle("D{$r}")->getFont()->getColor()->setRGB('166534');
                        elseif ($vf < 0)  $sheet->getStyle("D{$r}")->getFont()->getColor()->setRGB('991B1B');
                    }
                }

                // Outer border for whole sheet
                if ($lastRow > 1) {
                    $sheet->getStyle("A1:E{$lastRow}")->applyFromArray([
                        'borders' => [
                            'outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
                        ],
                    ]);
                }
            },
        ];
    }
}
