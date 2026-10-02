<?php

declare(strict_types=1);

namespace App\Exports\Finance;

use App\Services\Reports\RmPortfolioService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Top N loan-account gainers/losers per RM, side-by-side, all in one sheet.
 * Layout mirrors RmDepositMoversSheet — the deposit equivalent — but for the loan book.
 * Added as an extra sheet on the RM Movers workbook (daily + weekly), since loans are
 * already shown alongside deposits in those reports and no longer have a separate report.
 *
 * @param array<string,string> $rmNames rm_code => display name
 * @param array<string,array{gainers: array, losers: array}> $grouped from
 *        RmLoanMoversService::accountMoversGroupedByRmCodes(), keyed by rm_code — each
 *        account row shaped as ['account' => string, 'name' => string, 'movement' => float]
 */
class LoanAccountMoversByRmSheet implements FromArray, WithTitle, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    private array $mergeRows = [];
    private array $boldRows  = [];

    /** @var array<int, string> rmHeaderRow => segment name, for color-coding each RM's block */
    private array $rmHeaderSegments = [];

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
        return 'RM Loan Movers';
    }

    public function array(): array
    {
        $rows = [];

        $rows[] = ['Top Loan Movers by RM (Gainers vs Losers)'];
        $this->mergeRows[] = 1;
        $this->boldRows[]  = 1;

        $rows[] = ["Period: {$this->startDate} → {$this->endDate}  |  Performing book only, Corporate and Staff loans excluded"];
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
            $this->rmHeaderSegments[$rmHeaderRow] = RmPortfolioService::segment($code);

            $headerRow = count($rows) + 1;
            $rows[] = [
                'Rank', 'Account No.', 'Name', 'Movement', '',
                'Rank', 'Account No.', 'Name', 'Movement',
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
                    $g ? (string) $g['account'] : ($isEmpty ? '(no movers)' : ''),
                    $g ? (string) ($g['name'] ?? '') : '',
                    $g ? (float) ($g['movement'] ?? 0) : '',
                    '',
                    $l ? ($i + 1) : '',
                    $l ? (string) $l['account'] : ($isEmpty ? '(no movers)' : ''),
                    $l ? (string) ($l['name'] ?? '') : '',
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

                // Color-code each RM's block header by their Job Unit segment.
                foreach ($this->rmHeaderSegments as $row => $segment) {
                    $color = RmPortfolioService::segmentColor($segment);
                    $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color['fill']]],
                        'font' => ['bold' => true, 'color' => ['rgb' => $color['fillText']]],
                    ]);
                }

                $sheet->freezePane('A4');
            },
        ];
    }
}
