<?php

declare(strict_types=1);

namespace App\Exports\Finance;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Top N deposit CIF gainers/losers per RM, side-by-side, all in one sheet.
 * Layout mirrors CifMoversByBranchSheet (BranchMoversWorkbookExport.php) but grouped
 * by RM instead of branch — shared by both the daily and weekly RM Movers Excel exports.
 *
 * @param array<string,string> $rmNames rm_code => display name
 * @param array<string,array{gainers: array, losers: array}> $grouped from
 *        RmMoversService::drilldownGroupedByRmCodes(), keyed by rm_code
 */
class RmDepositMoversSheet implements FromArray, WithTitle, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    private array $mergeRows = [];
    private array $boldRows  = [];

    public function __construct(
        private readonly string $startDate,
        private readonly string $endDate,
        private readonly array $rmCodes,
        private readonly array $rmNames,
        private readonly array $grouped
    ) {
    }

    public function title(): string
    {
        return 'RM Deposit Movers';
    }

    public function array(): array
    {
        $rows = [];

        $rows[] = ['Top Deposit Movers by RM (Gainers vs Losers)'];
        $this->mergeRows[] = 1;
        $this->boldRows[]  = 1;

        $rows[] = ["Period: {$this->startDate} → {$this->endDate}"];
        $this->mergeRows[] = 2;

        $rows[] = [''];
        $this->mergeRows[] = 3;

        foreach ($this->rmCodes as $code) {
            $name  = $this->rmNames[$code] ?? $code;
            $title = "{$code} - {$name}";

            $rmHeaderRow = count($rows) + 1;
            $rows[] = [$title];
            $this->mergeRows[] = $rmHeaderRow;
            $this->boldRows[]  = $rmHeaderRow;

            $headerRow = count($rows) + 1;
            $rows[] = [
                'Rank', 'CIF', 'Customer Name', 'Movement', '',
                'Rank', 'CIF', 'Customer Name', 'Movement',
            ];
            $this->boldRows[] = $headerRow;

            $gainers = collect($this->grouped[$code]['gainers'] ?? []);
            $losers  = collect($this->grouped[$code]['losers']  ?? []);
            $isEmpty = $gainers->isEmpty() && $losers->isEmpty();

            $maxRows = max($gainers->count(), $losers->count(), 1);

            for ($i = 0; $i < $maxRows; $i++) {
                $g = $gainers->get($i);
                $l = $losers->get($i);

                $rows[] = [
                    $g ? ($i + 1) : '',
                    $g ? (string) $g['cif'] : ($isEmpty ? '(no movers)' : ''),
                    $g ? (string) ($g['customer_name'] ?? '') : '',
                    $g ? (float) ($g['movement'] ?? 0) : '',
                    '',
                    $l ? ($i + 1) : '',
                    $l ? (string) $l['cif'] : ($isEmpty ? '(no movers)' : ''),
                    $l ? (string) ($l['customer_name'] ?? '') : '',
                    $l ? (float) ($l['movement'] ?? 0) : '',
                ];
            }

            $rows[] = [''];
            $this->mergeRows[] = count($rows);
        }

        return array_map(fn ($r) => array_pad(is_array($r) ? $r : [$r], 9, ''), $rows);
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach ($this->mergeRows as $r) {
                    $sheet->mergeCells("A{$r}:I{$r}");
                }

                foreach ($this->boldRows as $r) {
                    $sheet->getStyle("A{$r}:I{$r}")->getFont()->setBold(true);
                }

                $sheet->freezePane('A4');
            },
        ];
    }
}
