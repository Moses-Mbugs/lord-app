<?php

declare(strict_types=1);

namespace App\Exports\Finance;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * One sheet of the monthly performance workbook: a title, a subtitle, then a stack of
 * tables ("blocks"), each with its own heading and column header row.
 *
 * Block shape: ['heading' => string, 'headers' => string[], 'rows' => array[],
 *               'totalRows' => int[] (indexes into rows), 'subRows' => int[] (indexes)]
 */
class MonthlyPerformanceSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private array $headingRows = [];
    private array $headerRows  = [];
    private array $totalRows   = [];
    private array $subRows     = [];
    private array $dataRows    = [];

    /**
     * @param string[] $movementCols Column letters coloured green/red by sign
     * @param string[] $numberCols   Column letters formatted #,##0
     */
    public function __construct(
        private readonly string $sheetTitle,
        private readonly string $heading,
        private readonly string $subtitle,
        private readonly array  $blocks,
        private readonly array  $movementCols,
        private readonly array  $numberCols,
    ) {}

    public function title(): string { return $this->sheetTitle; }

    public function array(): array
    {
        $width = max(1, ...array_map(fn($b) => count($b['headers']), $this->blocks ?: [['headers' => []]]));

        $rows   = [];
        $rows[] = array_pad([$this->heading], $width, '');
        $rows[] = array_pad([$this->subtitle], $width, '');

        foreach ($this->blocks as $block) {
            $rows[] = array_fill(0, $width, '');
            $rows[] = array_pad([$block['heading']], $width, '');
            $this->headingRows[] = count($rows);

            $rows[] = array_pad($block['headers'], $width, '');
            $this->headerRows[] = ['row' => count($rows), 'cols' => count($block['headers'])];

            if (empty($block['rows'])) {
                $rows[] = array_pad(['(no data)'], $width, '');
                continue;
            }

            foreach ($block['rows'] as $i => $r) {
                $rows[] = array_pad(array_values($r), $width, '');
                $rowNum = count($rows);
                $this->dataRows[] = $rowNum;
                if (in_array($i, $block['totalRows'] ?? [], true)) $this->totalRows[] = $rowNum;
                if (in_array($i, $block['subRows']   ?? [], true)) $this->subRows[]   = $rowNum;
            }
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = $sheet->getHighestColumn();

                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '002E4A']],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(26);
                $sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB('475569');

                foreach ($this->headingRows as $r) {
                    $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('0F172A');
                }

                foreach ($this->headerRows as $h) {
                    $end = Coordinate::stringFromColumnIndex($h['cols']);
                    $sheet->getStyle("A{$h['row']}:{$end}{$h['row']}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3A5F']],
                    ]);
                }

                foreach ($this->dataRows as $r) {
                    foreach ($this->numberCols as $col) {
                        $sheet->getStyle("{$col}{$r}")->getNumberFormat()->setFormatCode('#,##0;-#,##0;-');
                    }
                    foreach ($this->movementCols as $col) {
                        $v = $sheet->getCell("{$col}{$r}")->getValue();
                        if (!is_numeric($v) || (float) $v == 0.0) continue;
                        $sheet->getStyle("{$col}{$r}")->getFont()->getColor()->setRGB((float) $v > 0 ? '0B6E4F' : 'B00020');
                    }
                    $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getBorders()->getBottom()
                        ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('E2E8F0');
                }

                foreach ($this->subRows as $r) {
                    $sheet->getStyle("A{$r}")->getAlignment()->setIndent(2);
                    $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFont()->getColor()->setRGB('475569');
                }

                foreach ($this->totalRows as $r) {
                    $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                    ]);
                }
            },
        ];
    }
}
