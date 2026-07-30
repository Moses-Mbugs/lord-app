<?php
// app\Services\Finance\BalanceImportService.php

declare(strict_types=1);

namespace App\Services\Finance;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SplFileObject;
use Throwable;

class BalanceImportService
{
    /**
     * Parse the uploaded "BALANCES PER CUSTOMER" txt (tab-delimited) and insert into customer_balances.
     * We do NOT store the file anywhere (read from temp upload path).
     */
    public function importFromUpload(UploadedFile $file, ?string $balanceDate = null, int $batchSize = 2000): array
    {
        // Prevent memory issues due to query logging during huge inserts
        DB::disableQueryLog();
        @set_time_limit(0);

        $batchSize = max(200, min($batchSize, 10000));

        $realPath = $file->getRealPath();
        if (!$realPath || !is_readable($realPath)) {
            throw new \RuntimeException('Uploaded file is not readable (missing temp path).');
        }

        $filename = $file->getClientOriginalName() ?: 'uploaded.txt';

        // Determine balance date
        $date = $balanceDate ?: $this->guessDateFromFilename($filename) ?: now()->toDateString();

        // OPTIONAL: clean existing rows for that date (prevents duplicates)
        // If you want "append" behavior, remove this delete.
        DB::table('customer_balances')->whereDate('balance_date', $date)->delete();

        $inserted = 0;
        $skipped = 0;

        DB::beginTransaction();

        try {
            $fh = new SplFileObject($realPath);
            $fh->setFlags(SplFileObject::READ_AHEAD);

            $buffer = [];
            $now = now();

            while (!$fh->eof()) {
                $line = rtrim((string) $fh->fgets(), "\r\n");
                if ($line === '') continue;

                // Skip title/header
                if (Str::contains($line, 'BALANCES PER CUSTOMER')) continue;
                if (Str::startsWith($line, 'Branch Code')) continue;

                // TAB delimited
                $cols = str_getcsv($line, "\t");

                // We expect 10 cols (some may be empty)
                // [0]=branch [1]=acct [2]=cif [3]=name [4]=ccy [5]=avl [6]=acy [7]=lcy [8]=dr [9]=cr
                if (count($cols) < 8) {
                    $skipped++;
                    continue;
                }

                $branch = $this->clean($cols[0] ?? null);
                $acct   = $this->clean($cols[1] ?? null);
                $cif    = $this->clean($cols[2] ?? null);
                $ccy    = $this->clean($cols[4] ?? null);

                // pick ACY current if available else ACY available
                $acyAvl  = $this->clean($cols[5] ?? null);
                $acyCurr = $this->clean($cols[6] ?? null);
                $balStr  = ($acyCurr !== null && $acyCurr !== '') ? $acyCurr : (($acyAvl !== null && $acyAvl !== '') ? $acyAvl : '0');
                $balVal  = $this->toDecimal($balStr);

                if (!$branch || !$acct || !$ccy) {
                    $skipped++;
                    continue;
                }

                $buffer[] = [
                    'uploaded_file_id' => null,      // you said: do not store file
                    'cust_ac_no'       => $acct,
                    'currency'         => $ccy,
                    'currency_type'    => 'ACY',     // based on file columns
                    'branch_code'      => $branch,
                    'cif'              => $cif,
                    'cif_profile_id'   => null,
                    'balance_date'     => $date,
                    'balance'          => $balVal,
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

            if ($buffer) {
                DB::table('customer_balances')->insert($buffer);
                $inserted += count($buffer);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'balance_date' => $date,
            'inserted' => $inserted,
            'skipped' => $skipped,
            'filename' => $filename,
            'size' => $file->getSize(),
        ];
    }

    private function guessDateFromFilename(string $name): ?string
    {
        // Matches 12.01.2026
        if (preg_match('/(\d{2})\.(\d{2})\.(\d{4})/', $name, $m)) {
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
        return number_format((float) $v, 2, '.', '');
    }
}
