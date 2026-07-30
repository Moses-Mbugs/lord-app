<?php

declare(strict_types=1);

namespace App\Services\Finance;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SplFileObject;
use Throwable;

class CustomerBalancesImportService
{
    /**
     * Reads the uploaded file directly from temp path (no storing),
     * parses it, inserts into customer_balances, and logs in uploaded_files.
     */
    public function importFromUpload(
        UploadedFile $file,
        ?string $forcedDate = null,
        int $batchSize = 2000
    ): array {
        $batchSize = max(100, $batchSize);

        $tmpPath = $file->getRealPath();
        if (!$tmpPath || !is_readable($tmpPath)) {
            throw new \RuntimeException('Uploaded temp file is not readable.');
        }

        $originalName = $file->getClientOriginalName() ?: 'balances_upload.txt';
        $fileDate = $forcedDate ?: $this->parseDateFromFilename($originalName) ?: now()->toDateString();

        // checksum from temp file contents (still ok even though we don't store)
        $checksum = $this->sha256($tmpPath);
        if ($checksum === '') {
            throw new \RuntimeException('Could not compute checksum for uploaded file.');
        }

        $now = now();

        // create/update uploaded_files header (stored_path intentionally NULL)
        DB::table('uploaded_files')->updateOrInsert(
            [
                'file_type' => 'balances',
                'checksum'  => $checksum,
            ],
            [
                'original_name' => $originalName,
                'file_date'     => $fileDate,
                'stored_path'   => null,          // ✅ NOT storing anything
                'status'        => 'importing',
                'error'         => null,
                'meta'          => json_encode([
                    'source' => 'upload',
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]),
                'imported_at'   => $now,
                'updated_at'    => $now,
                'created_at'    => $now,
            ]
        );

        $uploadedFileId = (int) DB::table('uploaded_files')
            ->where('file_type', 'balances')
            ->where('checksum', $checksum)
            ->value('id');

        if ($uploadedFileId <= 0) {
            throw new \RuntimeException('Could not create/find uploaded_files row.');
        }

        $inserted = 0;
        $skipped  = 0;

        DB::beginTransaction();

        try {
            // clear any prior rows for that same checksum import (re-upload same file)
            DB::table('customer_balances')->where('uploaded_file_id', $uploadedFileId)->delete();

            $fh = new SplFileObject($tmpPath);
            $fh->setFlags(SplFileObject::READ_AHEAD);

            $buffer = [];

            while (!$fh->eof()) {
                $line = rtrim((string) $fh->fgets(), "\r\n");
                if ($line === '') continue;

                // skip title/header
                if (Str::contains($line, 'BALANCES PER CUSTOMER')) continue;
                if (Str::startsWith($line, 'Branch Code')) continue;

                // TAB-delimited
                $cols = str_getcsv($line, "\t");

                // expect around 10 cols; tolerate >= 8
                if (count($cols) < 8) {
                    $skipped++;
                    continue;
                }

                $branchCode = $this->clean($cols[0] ?? null);
                $custAcNo   = $this->clean($cols[1] ?? null);
                $cif        = $this->clean($cols[2] ?? null);
                $currency   = $this->clean($cols[4] ?? null);

                // balances: col[5] maybe blank; col[6] usually has value
                $acyAvl  = $this->clean($cols[5] ?? null);
                $acyCurr = $this->clean($cols[6] ?? null);

                $balanceStr = ($acyCurr !== null && $acyCurr !== '')
                    ? $acyCurr
                    : (($acyAvl !== null && $acyAvl !== '') ? $acyAvl : '0');

                $balanceVal = $this->toDecimal($balanceStr);

                // required keys
                if ($branchCode === null || $custAcNo === null || $currency === null) {
                    $skipped++;
                    continue;
                }

                // Map to LCY/FCY (since your top movers expects this)
                $currencyType = (strtoupper($currency) === 'KES') ? 'LCY' : 'FCY';

                $buffer[] = [
                    'uploaded_file_id' => $uploadedFileId,
                    'cust_ac_no'       => $custAcNo,
                    'currency'         => $currency,
                    'currency_type'    => $currencyType,
                    'branch_code'      => $branchCode,
                    'cif'              => $cif,
                    'cif_profile_id'   => null,
                    'balance_date'     => $fileDate,
                    'balance'          => $balanceVal,
                    'raw'              => $line,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];

                if (count($buffer) >= $batchSize) {
                    DB::table('customer_balances')->insert($buffer);
                    $inserted += count($buffer);
                    $buffer = [];
                }
            }

            if (!empty($buffer)) {
                DB::table('customer_balances')->insert($buffer);
                $inserted += count($buffer);
            }

            DB::table('uploaded_files')
                ->where('id', $uploadedFileId)
                ->update([
                    'status' => 'imported',
                    'error'  => null,
                    'updated_at' => now(),
                ]);

            DB::commit();

            return [
                'uploaded_file_id' => $uploadedFileId,
                'file_date'        => $fileDate,
                'checksum'         => $checksum,
                'inserted'         => $inserted,
                'skipped'          => $skipped,
            ];
        } catch (Throwable $e) {
            DB::rollBack();

            DB::table('uploaded_files')
                ->where('id', $uploadedFileId)
                ->update([
                    'status' => 'failed',
                    'error'  => Str::limit($e->getMessage(), 1000),
                    'updated_at' => now(),
                ]);

            throw $e;
        }
    }

    private function sha256(string $path): string
    {
        $hash = @hash_file('sha256', $path);
        return $hash ?: '';
    }

    private function parseDateFromFilename(string $filename): ?string
    {
        // e.g. BALANCES PER CUSTOMER_v1_txt_12.01.2026.txt
        if (preg_match('/(\d{2})\.(\d{2})\.(\d{4})/', $filename, $m)) {
            return Carbon::createFromDate((int)$m[3], (int)$m[2], (int)$m[1])->format('Y-m-d');
        }
        return null;
    }

    private function clean(?string $v): ?string
    {
        if ($v === null) return null;
        $v = trim($v);
        return $v === '' ? null : $v;
    }

    private function toDecimal(string $v): string
    {
        $v = trim($v);
        $v = str_replace([',', ' '], ['', ''], $v);
        if ($v === '' || !is_numeric($v)) return '0.00';
        return number_format((float)$v, 2, '.', '');
    }
}
