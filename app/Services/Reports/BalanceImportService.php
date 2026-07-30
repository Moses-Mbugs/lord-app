<?php

namespace App\Services\Reports;

use App\Models\Finance\CifProfile;
use App\Models\Finance\CustomerBalance;
use App\Models\Finance\UploadedFile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BalanceImportService
{
    /**
     * Import balances txt files from a folder.
     * - Recursively scans for Kenya.zip and BALANCES PER CUSTOMER*.txt
     * - Extracts Kenya.zip into storage/app/analysis/...
     * - Parses tab-delimited txt
     * - Inserts into customer_balances (with raw JSON)
     */
    public function importFromPath(string $path): array
    {
        $path = $this->normalizePath($path);

        if (!is_dir($path)) {
            throw new \RuntimeException("Balance import path not found: {$path}");
        }

        $analysisBase = storage_path('app/analysis');
        File::ensureDirectoryExists($analysisBase);

        $filesProcessed = 0;
        $inserted = 0;
        $skipped = 0;

        // Extract Kenya.zip if present
        $zipPaths = $this->findFiles($path, fn($p) => strcasecmp(basename($p), 'Kenya.zip') === 0);
        foreach ($zipPaths as $zip) {
            $this->extractZipToAnalysis($zip, $analysisBase);
        }

        // Find balance txt files (source + extracted)
        $txtCandidates = array_merge(
            $this->findFiles($path, fn($p) => str_ends_with(strtolower($p), '.txt')),
            $this->findFiles($analysisBase, fn($p) => str_ends_with(strtolower($p), '.txt'))
        );

        $txtCandidates = array_values(array_filter($txtCandidates, function ($p) {
            return stripos(basename($p), 'BALANCES PER CUSTOMER') !== false;
        }));

        $txtCandidates = array_values(array_unique($txtCandidates));

        foreach ($txtCandidates as $txtPath) {
            $fileDate = $this->inferDateFromFilename(basename($txtPath));
            $currencyType = $this->inferCurrencyTypeFromFilename(basename($txtPath));

            $checksum = $this->sha256FileSafe($txtPath);

            $uploaded = UploadedFile::firstOrCreate(
                [
                    'file_type' => 'balances',
                    'original_name' => basename($txtPath),
                    'file_date' => $fileDate,
                ],
                [
                    'checksum' => $checksum,
                    'stored_path' => $txtPath,
                    'status' => 'imported',
                    'imported_at' => now(),
                ]
            );

            if (!$uploaded->wasRecentlyCreated) {
                $skipped++;
                continue;
            }

            $filesProcessed++;

            [$rows, $header] = $this->parseTabDelimitedFile($txtPath);

            if (empty($rows)) {
                $uploaded->update(['status' => 'failed', 'error' => 'No rows parsed']);
                continue;
            }

            $chunk = [];
            $chunkSize = 500;

            foreach ($rows as $r) {
                $mapped = $this->mapBalanceRow($r, $fileDate, $currencyType);

                $cif = $mapped['cif'];
                if ($cif) {
                    $profile = CifProfile::where('cif', $cif)->first();
                    if ($profile) {
                        $mapped['cif_profile_id'] = $profile->id;
                    }
                }

                $mapped['uploaded_file_id'] = $uploaded->id;

                $chunk[] = $mapped;

                if (count($chunk) >= $chunkSize) {
                    $inserted += $this->insertBalanceChunk($chunk);
                    $chunk = [];
                }
            }

            if (!empty($chunk)) {
                $inserted += $this->insertBalanceChunk($chunk);
            }
        }

        return [
            'files' => $filesProcessed,
            'inserted' => $inserted,
            'skipped' => $skipped,
        ];
    }

    private function insertBalanceChunk(array $rows): int
    {
        DB::table('customer_balances')->insert($rows);
        return count($rows);
    }

    private function mapBalanceRow(array $row, ?string $fileDate, string $currencyType): array
    {
        $n = $this->normalizeKeys($row);

        $custAc = $n['cust ac no'] ?? $n['cust_ac_no'] ?? $n['account'] ?? $n['account no'] ?? $n['account_no'] ?? null;

        // "Cust No" in your file = CIF
        $cif = $n['cust no'] ?? $n['cust_no'] ?? $n['cust no.'] ?? $n['cust no'] ?? $n['cif'] ?? $n['customer id'] ?? $n['customerid'] ?? null;

        // "Ac Desc" in your file = Name/Description
        $acDesc = $n['ac desc'] ?? $n['ac_desc'] ?? $n['account desc'] ?? $n['account description'] ?? null;

        $currency = $n['ccy'] ?? $n['currency'] ?? $n['curr'] ?? 'KES';

        // balance guesses
        $balRaw = $n['balance'] ?? $n['lcy curr balance'] ?? $n['acy curr balance'] ?? $n['acy avl bal'] ?? $n['available balance'] ?? null;
        $balance = $this->toDecimal($balRaw);

        $branch = $n['branch code'] ?? $n['branch'] ?? $n['branch_code'] ?? null;

        $balanceDate = $fileDate ?: now()->toDateString();

        $name = $acDesc ? strtoupper(trim((string) $acDesc)) : null;

        return [
            'cust_ac_no'     => $custAc ?: 'UNKNOWN',
            'cif'            => $cif,
            'customer_name'  => $name,           // ✅ NEW
            'account_desc'   => $name,           // ✅ OPTIONAL (same as customer_name here)
            'cif_profile_id' => null,
            'balance_date'   => $balanceDate,
            'currency'       => strtoupper(trim((string)$currency ?: 'KES')),
            'currency_type'  => $currencyType,
            'balance'        => $balance,
            'branch_code'    => $branch,
            'raw'            => $row,
            'created_at'     => now(),
            'updated_at'     => now(),
        ];
    }

    private function parseTabDelimitedFile(string $path): array
    {
        $lines = @file($path, FILE_IGNORE_NEW_LINES);
        if (!$lines) return [[], []];

        // Find header line: prefer one containing "CUST" or "ACCOUNT" etc
        $headerLineIndex = null;
        foreach ($lines as $i => $line) {
            $lineTrim = trim($line);
            if ($lineTrim === '') continue;

            if (stripos($lineTrim, 'BALANCES PER CUSTOMER') !== false) continue;

            if (stripos($lineTrim, 'CUST') !== false || stripos($lineTrim, 'ACCOUNT') !== false) {
                // likely header
                $headerLineIndex = $i;
                break;
            }
        }
        if ($headerLineIndex === null) {
            foreach ($lines as $i => $line) {
                if (trim($line) !== '') { $headerLineIndex = $i; break; }
            }
        }
        if ($headerLineIndex === null) return [[], []];

        $header = array_map('trim', explode("\t", $lines[$headerLineIndex]));
        $rows = [];

        for ($i = $headerLineIndex + 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if ($line === '') continue;

            $cols = explode("\t", $lines[$i]);

            $assoc = [];
            foreach ($header as $idx => $h) {
                $assoc[$h] = $cols[$idx] ?? null;
            }

            $nonEmpty = array_filter($assoc, fn($v) => trim((string)$v) !== '');
            if (count($nonEmpty) === 0) continue;

            $rows[] = $assoc;
        }

        return [$rows, $header];
    }

    private function findFiles(string $root, callable $filter): array
    {
        $out = [];
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($it as $file) {
            /** @var \SplFileInfo $file */
            if (!$file->isFile()) continue;
            $p = $file->getPathname();
            if ($filter($p)) $out[] = $p;
        }

        return $out;
    }

    private function extractZipToAnalysis(string $zipPath, string $analysisBase): void
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException("ZipArchive not available. Install php-zip extension.");
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException("Failed to open zip: {$zipPath}");
        }

        $target = $analysisBase . '/extracted_' . md5($zipPath . microtime(true));
        File::ensureDirectoryExists($target);

        $zip->extractTo($target);
        $zip->close();
    }

    private function inferDateFromFilename(string $filename): ?string
    {
        if (preg_match('/(\d{2})[.\-_](\d{2})[.\-_](\d{4})/', $filename, $m)) {
            return Carbon::createFromFormat('d-m-Y', "{$m[1]}-{$m[2]}-{$m[3]}")->toDateString();
        }
        if (preg_match('/\b(\d{4})(\d{2})(\d{2})\b/', $filename, $m)) {
            return Carbon::createFromFormat('Y-m-d', "{$m[1]}-{$m[2]}-{$m[3]}")->toDateString();
        }
        if (preg_match('/\b(\d{4})-(\d{2})-(\d{2})\b/', $filename, $m)) {
            return Carbon::createFromFormat('Y-m-d', "{$m[1]}-{$m[2]}-{$m[3]}")->toDateString();
        }
        return null;
    }

    private function inferCurrencyTypeFromFilename(string $filename): string
    {
        $u = strtoupper($filename);
        if (str_contains($u, 'FCY') || str_contains($u, 'USD') || str_contains($u, 'EUR')) {
            return 'FCY';
        }
        return 'LCY';
    }

    private function toDecimal($value): float
    {
        if ($value === null) return 0.0;
        $s = trim((string)$value);
        if ($s === '') return 0.0;

        $s = str_replace([',', ' '], '', $s);

        $neg = false;
        if (str_starts_with($s, '(') && str_ends_with($s, ')')) {
            $neg = true;
            $s = trim($s, '()');
        }

        if (!is_numeric($s)) return 0.0;
        $f = (float)$s;
        return $neg ? -$f : $f;
    }

    private function normalizeKeys(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            $key = strtolower(trim((string)$k));
            $key = preg_replace('/\s+/', ' ', $key);
            $out[$key] = $v;
        }
        return $out;
    }

    private function sha256FileSafe(string $path): ?string
    {
        try {
            return hash_file('sha256', $path) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizePath(string $path): string
    {
        return rtrim($path, "/\\");
    }
}
