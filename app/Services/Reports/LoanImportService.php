<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class LoanImportService
{
    // Maps raw status codes → normalised bucket name
    private const STATUS_BUCKETS = [
        'NORM'        => 'Performing',
        'NORMAL'      => 'Performing',
        'OAEM'        => 'Watch',
        'WATCH'       => 'Watch',
        'SUB1'        => 'Substandard',
        'SUBS'        => 'Substandard',
        'SUBSTANDARD' => 'Substandard',
        'DOUB'        => 'Doubtful',
        'DOUBTFUL'    => 'Doubtful',
        'LOSS'        => 'Loss',
        'WOFF'        => 'Loss',
    ];

    private const SHEET_NAME = 'Loan Book';

    /**
     * Import a Loan Book Excel upload.
     * Reads the "Loan Book" sheet, stores rows into loan_listings.
     * Deletes any existing rows for the resolved date before inserting.
     */
    public function importFromUpload(UploadedFile $file, ?string $asAtDate = null, int $batchSize = 1000): array
    {
        DB::disableQueryLog();
        @set_time_limit(0);

        $batchSize = max(200, min($batchSize, 5000));
        $filename  = $file->getClientOriginalName() ?: 'loan_book.xlsx';
        $realPath  = $file->getRealPath();

        if (!$realPath || !is_readable($realPath)) {
            throw new \RuntimeException('Uploaded file is not readable.');
        }

        $date = $asAtDate ?: $this->guessDateFromFilename($filename) ?: now()->toDateString();

        // Replace existing data for this date
        DB::table('loan_listings')->whereDate('as_at_date', $date)->delete();

        // maatwebsite/excel reconfigures PhpSpreadsheet's cell cache backend
        // globally on boot, which affects this raw PhpSpreadsheet usage too.
        // readDataOnly skips styles we never use; the raised limit is scoped
        // to this request only.
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }

        $reader      = IOFactory::createReaderForFile($realPath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($realPath);
        $ws          = $spreadsheet->getSheetByName(self::SHEET_NAME);

        if (!$ws) {
            throw new \RuntimeException('Sheet "' . self::SHEET_NAME . '" not found in the uploaded file.');
        }

        // Build header → column-index map from row 1
        $headerMap = [];
        $highestCol = $ws->getHighestColumn();
        $highestColIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

        for ($c = 1; $c <= $highestColIdx; $c++) {
            $header = trim((string) $ws->getCellByColumnAndRow($c, 1)->getValue());
            if ($header !== '') {
                $headerMap[$header] = $c;
            }
        }

        $required = ['related_account', 'related_customer_id', 'name', 'branch', 'currency',
                     'business_segment', 'status', 'loan_book_outstanding', 'outstanding_amount_lcy',
                     'product_code', 'pms_gl_codes', 'linecode'];

        foreach ($required as $col) {
            if (!isset($headerMap[$col])) {
                throw new \RuntimeException("Expected column \"{$col}\" not found in Loan Book sheet.");
            }
        }

        $highestRow = $ws->getHighestRow();
        $inserted   = 0;
        $skipped    = 0;
        $buffer     = [];
        $now        = now();

        DB::beginTransaction();

        try {
            for ($row = 2; $row <= $highestRow; $row++) {
                $get = fn(string $col) => trim((string) ($ws->getCellByColumnAndRow($headerMap[$col], $row)->getValue() ?? ''));
                // Safe getter for optional columns that may not exist in every file version
                $getSafe = fn(string $col) => isset($headerMap[$col])
                    ? trim((string) ($ws->getCellByColumnAndRow($headerMap[$col], $row)->getValue() ?? ''))
                    : '';

                $account  = $get('related_account');
                $currency = strtoupper($get('currency'));

                if ($account === '' || $currency === '') {
                    $skipped++;
                    continue;
                }

                // Exclude Head Office entries from the loan book entirely
                $branchName = $getSafe('branch_name');
                if (stripos($branchName, 'HEAD OFFICE') !== false) {
                    $skipped++;
                    continue;
                }

                $rawStatus    = strtoupper($get('status'));
                $statusBucket = self::STATUS_BUCKETS[$rawStatus] ?? 'Other';
                $currencyType = ($currency === 'KES') ? 'LCY' : 'FCY';

                $outstanding    = $this->toDecimal($get('loan_book_outstanding'));
                $outstandingLcy = $this->toDecimal($get('outstanding_amount_lcy'));

                // Build a compact raw snapshot for audit/debug
                $rawData = [
                    'source_report' => $getSafe('source_report'),
                    'account_status' => $getSafe('account_status'),
                    'interest_rate' => $getSafe('interest_rate'),
                    'tenor' => $getSafe('tenor'),
                    'limit' => $getSafe('limit'),
                    'value_dt' => $getSafe('value_dt'),
                    'maturity_date' => $getSafe('maturity_date'),
                    'rm_officer' => $getSafe('rm_officer'),
                ];

                $buffer[] = [
                    'as_at_date'             => $date,
                    'related_account'        => $account,
                    'cif'                    => $get('related_customer_id') ?: null,
                    'name'                   => $get('name') ?: null,
                    'branch'                 => $get('branch') ?: (strlen($account) >= 3 ? strtoupper(substr($account, 0, 3)) : null),
                    'source_type'            => strtoupper($getSafe('source_type')) ?: null,
                    'branch_name'            => $branchName ?: null,
                    'currency'               => $currency,
                    'currency_type'          => $currencyType,
                    'business_segment'       => strtoupper($get('business_segment')) ?: null,
                    'loan_status'            => $rawStatus ?: null,
                    'status_bucket'          => $statusBucket,
                    'loan_book_outstanding'  => $outstanding,
                    'outstanding_amount_lcy' => $outstandingLcy,
                    'product_code'           => $get('product_code') ?: null,
                    'pms_gl_codes'           => $get('pms_gl_codes') ?: null,
                    'linecode'               => $get('linecode') ?: null,
                    'raw'                    => json_encode($rawData),
                    'created_at'             => $now,
                    'updated_at'             => $now,
                ];

                if (count($buffer) >= $batchSize) {
                    DB::table('loan_listings')->insert($buffer);
                    $inserted += count($buffer);
                    $buffer = [];
                }
            }

            if ($buffer) {
                DB::table('loan_listings')->insert($buffer);
                $inserted += count($buffer);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'as_at_date' => $date,
            'inserted'   => $inserted,
            'skipped'    => $skipped,
            'filename'   => $filename,
        ];
    }

    /**
     * Parse date from filenames like:
     *   Loan_Book_LB-20260610-113429-OXFVN.xlsx  → 2026-06-10
     *   LoanBook_20260610.xlsx                    → 2026-06-10
     */
    public function guessDateFromFilename(string $name): ?string
    {
        // Pattern: 8-digit YYYYMMDD somewhere in the name
        if (preg_match('/(\d{4})(\d{2})(\d{2})/', $name, $m)) {
            try {
                return Carbon::createFromDate((int)$m[1], (int)$m[2], (int)$m[3])->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }
        return null;
    }

    private function toDecimal(string $v): float
    {
        $v = trim(str_replace([',', ' '], '', $v));
        return is_numeric($v) ? (float) $v : 0.0;
    }
}
