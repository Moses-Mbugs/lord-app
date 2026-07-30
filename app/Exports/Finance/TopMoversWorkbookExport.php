<?php

declare(strict_types=1);

namespace App\Exports\Finance;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TopMoversWorkbookExport implements WithMultipleSheets
{
    public function __construct(
        private array      $grouped,
        private string     $start,
        private string     $end,
        private ?Collection $segments = null
    ) {}

    public function sheets(): array
    {
        return [
            // Sheet 1: Executive summary cover
            new TopMoversCoverSheet(
                $this->grouped,
                $this->start,
                $this->end,
                $this->segments ?? collect()
            ),

            // CIF ONLY — all segments (KES equivalent), top 20 each
            new TopMoversSheetExport(
                rows: $this->grouped['CIF_ONLY']['GAIN'] ?? collect(),
                sheetName: 'CIF Gainers',
                start: $this->start,
                end: $this->end,
                scope: TopMoversSheetExport::SCOPE_CIF_ONLY
            ),
            new TopMoversSheetExport(
                rows: $this->grouped['CIF_ONLY']['LOSS'] ?? collect(),
                sheetName: 'CIF Losers',
                start: $this->start,
                end: $this->end,
                scope: TopMoversSheetExport::SCOPE_CIF_ONLY
            ),

            // CIF + Currency
            new TopMoversSheetExport(
                rows: $this->grouped['CIF_CURRENCY']['LCY']['GAIN'] ?? collect(),
                sheetName: 'LCY Gainers',
                start: $this->start,
                end: $this->end,
                scope: TopMoversSheetExport::SCOPE_CIF_CURRENCY
            ),
            new TopMoversSheetExport(
                rows: $this->grouped['CIF_CURRENCY']['LCY']['LOSS'] ?? collect(),
                sheetName: 'LCY Losers',
                start: $this->start,
                end: $this->end,
                scope: TopMoversSheetExport::SCOPE_CIF_CURRENCY
            ),
            new TopMoversSheetExport(
                rows: $this->grouped['CIF_CURRENCY']['FCY']['GAIN'] ?? collect(),
                sheetName: 'FCY Gainers',
                start: $this->start,
                end: $this->end,
                scope: TopMoversSheetExport::SCOPE_CIF_CURRENCY
            ),
            new TopMoversSheetExport(
                rows: $this->grouped['CIF_CURRENCY']['FCY']['LOSS'] ?? collect(),
                sheetName: 'FCY Losers',
                start: $this->start,
                end: $this->end,
                scope: TopMoversSheetExport::SCOPE_CIF_CURRENCY
            ),
        ];
    }
}
