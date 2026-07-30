<?php

declare(strict_types=1);

namespace App\Services\customer_profitability;

use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelParserService
{
    private const YTD_KEYWORDS = ['ytd', 'year to date', 'profitability ytd'];

    private const MONTHLY_KEYWORDS = ['monthly', 'per customer', 'month'];

    private const YTD_HEADER_MAP = [
        'cif'                          => 'cif',
        'account'                      => 'cif',
        'account number'               => 'cif',
        'cif number'                   => 'cif',
        'customer name'                => 'name',
        'name'                         => 'name',
        'customer'                     => 'name',
        'segment'                      => 'segment',
        'seg'                          => 'segment',
        'rm'                           => 'rm',
        'relationship manager'         => 'rm',
        'relationship mgr'             => 'rm',
        'account officer'              => 'rm',
        'interest from loans'          => 'interest_from_loans',
        'loan interest'                => 'interest_from_loans',
        'interest from ods'            => 'interest_from_ods',
        'interest from od'             => 'interest_from_ods',
        'od interest'                  => 'interest_from_ods',
        'overdraft interest'           => 'interest_from_ods',
        'interest from trade'          => 'interest_from_trade',
        'trade interest'               => 'interest_from_trade',
        'total interest income'        => 'total_interest_income',
        'total interest'               => 'total_interest_income',
        'interest paid'                => 'interest_paid',
        'interest expense'             => 'interest_paid',
        'ftp income'                   => 'ftp_income',
        'ftp expense'                  => 'ftp_expense',
        'net ftp interest'             => 'net_ftp_interest',
        'ftp'                          => 'net_ftp_interest',
        'net interest income'          => 'net_interest_income',
        'nii'                          => 'net_interest_income',
        'payments'                     => 'payments',
        'receivables'                  => 'receivables',
        'liquidity'                    => 'liquidity',
        'cash management'              => 'cash_management',
        'fees and commissions'         => 'fees_and_commissions',
        'fees & commissions'           => 'fees_and_commissions',
        'commissions'                  => 'fees_and_commissions',
        'trade fees'                   => 'trade_fees',
        'acquiring expense'            => 'acquiring_expense',
        'acquring expense'             => 'acquiring_expense',   // typo variant in source file
        'acquiring'                    => 'acquiring_expense',
        'total fees'                   => 'total_fees',
        'fx income'                    => 'fx_income',
        'foreign exchange'             => 'fx_income',
        'forex'                        => 'fx_income',
        'other income'                 => 'other_income',
        'total revenue'                => 'total_revenue',
        'revenue'                      => 'total_revenue',
    ];

    private const MONTHLY_HEADER_MAP = [
        'cif'                          => 'cif',
        'account'                      => 'cif',
        'account number'               => 'cif',
        'cif number'                   => 'cif',
        'customer name'                => 'name',
        'name'                         => 'name',
        'customer'                     => 'name',
        'segment'                      => 'segment',
        'seg'                          => 'segment',
        'rm'                           => 'rm',
        'relationship manager'         => 'rm',
        'relationship mgr'             => 'rm',
        'account officer'              => 'rm',
        'month'                        => 'month',
        'period'                       => 'month',
        'interest from loans'          => 'interest_from_loans',
        'loan interest'                => 'interest_from_loans',
        'interest from ods'            => 'interest_from_ods',
        'interest from od'             => 'interest_from_ods',
        'od interest'                  => 'interest_from_ods',
        'overdraft interest'           => 'interest_from_ods',
        'interest from trade'          => 'interest_from_trade',
        'trade interest'               => 'interest_from_trade',
        'total interest income'        => 'total_interest_income',
        'total interest'               => 'total_interest_income',
        'interest paid'                => 'interest_paid',
        'interest expense'             => 'interest_paid',
        'ftp income'                   => 'ftp_income',
        'ftp expense'                  => 'ftp_expense',
        'net ftp interest'             => 'net_ftp_interest',
        'net interest income'          => 'net_interest_income',
        'nii'                          => 'net_interest_income',
        'payments'                     => 'payments',
        'receivables'                  => 'receivables',
        'liquidity'                    => 'liquidity',
        'cash management'              => 'cash_management',
        'fees and commissions'         => 'fees_and_commissions',
        'fees & commissions'           => 'fees_and_commissions',
        'commissions'                  => 'fees_and_commissions',
        'trade fees'                   => 'trade_fees',
        'acquiring expense'            => 'acquiring_expense',
        'acquring expense'             => 'acquiring_expense',   // typo variant
        'acquiring'                    => 'acquiring_expense',
        'total fees'                   => 'total_fees',
        'fx income'                    => 'fx_income',
        'foreign exchange'             => 'fx_income',
        'forex'                        => 'fx_income',
        'other income'                 => 'other_income',
        'total revenue'                => 'total_revenue',
        'revenue'                      => 'total_revenue',
        'casa lcy'                     => 'casa_lcy',
        'casa local'                   => 'casa_lcy',
        'casa fcy'                     => 'casa_fcy',
        'casa foreign'                 => 'casa_fcy',
        'term deposits lcy'            => 'term_deposits_lcy',
        'term deposits local'          => 'term_deposits_lcy',
        'term deposits fcy'            => 'term_deposits_fcy',
        'term deposits foreign'        => 'term_deposits_fcy',
        'total deposits'               => 'total_deposits',
        'loans lcy'                    => 'loans_lcy',
        'loans fcy'                    => 'loans_fcy',
        'od lcy'                       => 'od_lcy',
        'overdraft lcy'                => 'od_lcy',
        'od fcy'                       => 'od_fcy',
        'overdraft fcy'                => 'od_fcy',
        'gross loans'                  => 'gross_loans',
    ];

    /**
     * @return array{ytd_label: string|null, ytd: array, monthly: array}
     */
    public function parse(string $path): array
    {
        $spreadsheet = IOFactory::load($path);

        $allSheets = array_map(fn($s) => $s->getTitle(), $spreadsheet->getAllSheets());
        Log::info('[CP] Sheets: ' . implode(', ', $allSheets));

        $ytdSheet     = $this->detectSheet($spreadsheet, self::YTD_KEYWORDS);
        $monthlySheet = $this->detectSheet($spreadsheet, self::MONTHLY_KEYWORDS, $ytdSheet);

        Log::info('[CP] YTD sheet: '     . ($ytdSheet?->getTitle()     ?? 'none'));
        Log::info('[CP] Monthly sheet: ' . ($monthlySheet?->getTitle() ?? 'none'));

        $ytdHeader     = $ytdSheet     ? $this->detectHeaderRow($ytdSheet,     self::YTD_HEADER_MAP)     : 1;
        $monthlyHeader = $monthlySheet ? $this->detectHeaderRow($monthlySheet, self::MONTHLY_HEADER_MAP) : 1;

        $ytd     = $ytdSheet     ? $this->parseSheet($ytdSheet,     self::YTD_HEADER_MAP,     $ytdHeader)     : [];
        $monthly = $monthlySheet ? $this->parseSheet($monthlySheet, self::MONTHLY_HEADER_MAP, $monthlyHeader) : [];

        // YTD rows may carry a period label in the 'month' field (e.g. "Juneytd 2025").
        // Extract it as ytd_label, then clear month from all ytd rows.
        $ytdLabel = $ytdSheet?->getTitle();
        foreach ($ytd as $row) {
            $m = $row['month'] ?? null;
            if ($m !== null && $m !== '') {
                $ytdLabel = (string) $m;
                break;
            }
        }
        $ytd = array_map(fn($r) => array_merge($r, ['month' => null]), $ytd);

        Log::info("[CP] YTD rows: " . count($ytd) . "  Monthly rows: " . count($monthly));

        return [
            'ytd_label' => $ytdLabel,
            'ytd'       => $ytd,
            'monthly'   => $monthly,
        ];
    }

    // ── Sheet detection ───────────────────────────────────────────────────────

    private function detectSheet(
        \PhpOffice\PhpSpreadsheet\Spreadsheet $book,
        array $keywords,
        ?Worksheet $exclude = null
    ): ?Worksheet {
        foreach ($book->getAllSheets() as $sheet) {
            if ($exclude && $sheet->getTitle() === $exclude->getTitle()) {
                continue;
            }
            $title = strtolower($sheet->getTitle());
            foreach ($keywords as $kw) {
                if (str_contains($title, $kw)) {
                    return $sheet;
                }
            }
        }

        foreach ($book->getAllSheets() as $sheet) {
            if (!$exclude || $sheet->getTitle() !== $exclude->getTitle()) {
                return $sheet;
            }
        }

        return null;
    }

    // ── Header-row detection ──────────────────────────────────────────────────

    /**
     * Scan the first $maxScan rows and return the row number that has the most
     * cells matching the given header map. Falls back to row 1 if nothing matches.
     */
    private function detectHeaderRow(Worksheet $sheet, array $headerMap, int $maxScan = 5): int
    {
        $highestCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $bestRow    = 1;
        $bestScore  = 0;

        for ($r = 1; $r <= $maxScan; $r++) {
            $score = 0;
            for ($c = 1; $c <= $highestCol; $c++) {
                $raw = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($c) . $r)->getValue());
                if ($raw === '') {
                    continue;
                }
                $key = strtolower(preg_replace('/\s+/', ' ', $raw));
                if (isset($headerMap[$key])) {
                    $score++;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRow   = $r;
            }
        }

        Log::info("[CP] Sheet \"{$sheet->getTitle()}\": header row = $bestRow (score=$bestScore)");
        return $bestRow;
    }

    // ── Sheet parsing ─────────────────────────────────────────────────────────

    private function parseSheet(Worksheet $sheet, array $headerMap, int $headerRow = 1): array
    {
        $highestCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $highestRow = (int) $sheet->getHighestRow();

        $colToField = [];
        $rawHeaders = [];
        for ($c = 1; $c <= $highestCol; $c++) {
            $raw = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($c) . $headerRow)->getValue());
            if ($raw === '') {
                continue;
            }
            $key = strtolower(preg_replace('/\s+/', ' ', $raw));
            $rawHeaders[] = $raw;
            if (isset($headerMap[$key])) {
                $colToField[$c] = $headerMap[$key];
            }
        }

        Log::info('[CP] Sheet "' . $sheet->getTitle() . '" headers: ' . implode(' | ', $rawHeaders));
        Log::info('[CP] Matched fields: ' . implode(', ', array_values($colToField)));

        if (empty($colToField)) {
            return [];
        }

        $rows = [];
        for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
            $row = [];
            foreach ($colToField as $c => $field) {
                $cell  = $sheet->getCell(Coordinate::stringFromColumnIndex($c) . $r);
                $value = $cell->getValue();

                if ($cell->getDataType() === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA) {
                    try {
                        $value = $cell->getCalculatedValue();
                    } catch (\Throwable) {
                        $value = null;
                    }
                }

                $row[$field] = $this->coerce($field, $value);
            }

            if ($this->isEmptyRow($row)) {
                continue;
            }

            if (isset($row['month'])) {
                $row['month'] = $this->normaliseMonth($row['month']);
            }

            $rows[] = $row;
        }

        return $rows;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function coerce(string $field, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return match (true) {
                in_array($field, ['cif', 'name', 'segment', 'rm', 'month'], true) => null,
                default => 0.0,
            };
        }

        return match (true) {
            in_array($field, ['cif', 'name', 'segment', 'rm', 'month'], true) => trim((string) $value),
            default => (float) $value,
        };
    }

    private function isEmptyRow(array $row): bool
    {
        foreach (['cif', 'name'] as $key) {
            if (isset($row[$key]) && $row[$key] !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * Normalise various month representations to "YYYY-MM".
     * Returns null if the value cannot be parsed as a date.
     */
    private function normaliseMonth(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_numeric($raw)) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $raw);
                return $date->format('Y-m');
            } catch (\Throwable) {
                return null;
            }
        }

        $str = trim((string) $raw);

        if (preg_match('/^(\d{4})-(\d{2})$/', $str)) {
            return $str;
        }

        if (preg_match('/^(\d{1,2})[\/\-](\d{4})$/', $str, $m)) {
            return sprintf('%04d-%02d', $m[2], $m[1]);
        }

        if (preg_match('/^([A-Za-z]{3})[\s\-](\d{2,4})$/', $str, $m)) {
            $year  = strlen($m[2]) === 2 ? '20' . $m[2] : $m[2];
            $month = date('m', strtotime('1 ' . $m[1] . ' 2000'));
            if ($month) {
                return sprintf('%04d-%02d', $year, $month);
            }
        }

        try {
            return \Carbon\Carbon::parse($str)->format('Y-m');
        } catch (\Throwable) {
            // Non-date string (e.g. "Juneytd 2025" period label) — caller handles it
            return null;
        }
    }
}
