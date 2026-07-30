<?php

declare(strict_types=1);

namespace App\Services\Finance;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SplFileObject;

/**
 * Handles the complete import pipeline for a single Flexcube balance file.
 *
 * Key improvements over the original:
 *
 *  1. Per-connection TEMPORARY TABLE — not a shared staging table.
 *     Multiple workers can run concurrently with zero cross-worker locking.
 *
 *  2. Fewer DB round-trips — rejects and main insert are separate statements
 *     but both use ROW_COUNT() to avoid an extra COUNT(*) scan.
 *
 *  3. Pre-compiled regex patterns as class constants — no re-compilation
 *     on every line during file preprocessing.
 *
 *  4. Skip-if-already-imported guard — avoids reprocessing files whose
 *     checksum is already marked 'imported' in uploaded_files.
 *
 *  5. Cleaner error surface — a single public method, all internals private.
 */
class BalanceFileImporter
{
    // ── Pre-compiled patterns ─────────────────────────────────────────────────────

    /**
     * A valid record start: branch code is either a letter + digits (e.g. P22)
     * or purely numeric (e.g. 834), always followed by a tab.
     */
    private const ROW_START_RE  = '/^([A-Za-z]\d+|\d+)\t/';

    /** ISO 4217: exactly three uppercase letters */
    private const CURRENCY_RE   = '/^[A-Z]{3}$/';

    /** DD.MM.YYYY in the filename */
    private const DATE_NAME_RE  = '/(\d{2})\.(\d{2})\.(\d{4})/';

    /** /YYYY/Mon/D/ in the file path */
    private const DATE_PATH_RE  = '/\/(20\d{2})\/([A-Za-z]{3})\/(\d{1,2})\//';

    /** Columns after currency: acy_avl, acy_cur, lcy_cur, dr_gl, cr_gl */
    private const TAIL_COLS = 5;

    // ─────────────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Import a single balance file.
     *
     * @return array{int, int, array<string, int>}  [rows_inserted, rows_skipped, phase_timings_ms]
     * @throws \RuntimeException|\Throwable
     */
    public function import(string $filePath, bool $keepRaw = false, bool $force = false): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \RuntimeException("File not readable: {$filePath}");
        }

        $timings    = [];
        $importT0   = microtime(true);
        $phaseStart = $importT0;
        $lap        = function (string $label) use (&$phaseStart, &$timings): void {
            $now = microtime(true);
            $timings[$label] = round(($now - $phaseStart) * 1000);
            $phaseStart = $now;
        };

        $checksum = $this->sha256($filePath);
        $fileDate = $this->parseFileDate($filePath);
        $now      = now()->format('Y-m-d H:i:s');
        $lap('checksum_ms');

        // ── Guard: skip files already successfully imported ───────────────────────
        if (!$force) {
            $existingStatus = DB::table('uploaded_files')
                ->where('file_type', 'balances')
                ->where('checksum', $checksum)
                ->value('status');

            if ($existingStatus === 'imported') {
                return [0, 0, ['skipped_already_imported_ms' => round((microtime(true) - $importT0) * 1000)]];
            }
        }

        // ── Register / update uploaded_files row ─────────────────────────────────
        DB::table('uploaded_files')->updateOrInsert(
            ['file_type' => 'balances', 'checksum'  => $checksum],
            [
                'original_name' => basename($filePath),
                'file_date'     => $fileDate,
                'stored_path'   => $filePath,
                'status'        => 'importing',
                'error'         => null,
                'meta'          => null,
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
            throw new \RuntimeException("Could not create/find uploaded_files row for: {$filePath}");
        }

        $lap('register_upload_row_ms');

        // ── Session speed knobs ───────────────────────────────────────────────────
        foreach (['unique_checks=0', 'foreign_key_checks=0', 'local_infile=1'] as $knob) {
            try {
                DB::statement("SET SESSION {$knob}");
            } catch (\Throwable $e) {
                Log::warning("balances import: failed to set session {$knob}", [
                    'file'  => basename($filePath),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $tmp               = null;
        $stageName         = null;
        $computedStageName = null;

        try {
            // ── 1. Preprocess file into a load-ready temp file ────────────────────
            //    (strips title/header rows, normalises multi-line records to 10 cols)
            $tmp = $this->makeLoadReadyFile($filePath);
            $lap('preprocess_ms');

            // ── 2. Create a per-connection TEMPORARY TABLE ────────────────────────
            //    This is connection-scoped and drops automatically.
            //    Multiple workers can run in parallel — no shared staging table.
            $stageName = 'tmp_bal_' . getmypid() . '_' . substr(md5(uniqid('', true)), 0, 8);

            DB::statement("
                CREATE TEMPORARY TABLE `{$stageName}` (
                    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    uploaded_file_id BIGINT UNSIGNED NOT NULL,
                    balance_date     DATE            NOT NULL,
                    branch_code      VARCHAR(20),
                    cust_ac_no       VARCHAR(50),
                    cif              VARCHAR(30),
                    ac_desc          VARCHAR(255),
                    currency         VARCHAR(3),
                    acy_avl_raw      VARCHAR(30),
                    acy_cur_raw      VARCHAR(30),
                    lcy_cur_raw      VARCHAR(30),
                    dr_gl            VARCHAR(20),
                    cr_gl            VARCHAR(20),
                    raw              TEXT NULL,
                    created_at       DATETIME,
                    updated_at       DATETIME
                ) ENGINE=InnoDB
            ");
            $lap('create_temp_table_ms');

            // ── 3. LOAD DATA LOCAL INFILE → temp table (fast bulk load) ──────────
            $pdo     = DB::connection()->getPdo();
            $qTmp    = $pdo->quote($tmp);
            $rawExpr = $keepRaw
                ? "CONCAT_WS('\t',@branch_code,@cust_ac_no,@cif,@ac_desc,@currency,@acy_avl,@acy_cur,@lcy_cur,@dr_gl,@cr_gl)"
                : 'NULL';

            DB::statement("
                LOAD DATA LOCAL INFILE {$qTmp}
                INTO TABLE `{$stageName}`
                CHARACTER SET utf8mb4
                FIELDS TERMINATED BY '\t'
                LINES  TERMINATED BY '\n'
                (
                    @branch_code, @cust_ac_no, @cif, @ac_desc, @currency,
                    @acy_avl, @acy_cur, @lcy_cur, @dr_gl, @cr_gl
                )
                SET
                    uploaded_file_id = {$uploadedFileId},
                    balance_date     = '{$fileDate}',
                    branch_code      = NULLIF(TRIM(@branch_code), ''),
                    cust_ac_no       = NULLIF(TRIM(@cust_ac_no),  ''),
                    cif              = NULLIF(TRIM(@cif),          ''),
                    ac_desc          = NULLIF(TRIM(@ac_desc),      ''),
                    currency         = NULLIF(TRIM(@currency),     ''),
                    acy_avl_raw      = NULLIF(TRIM(@acy_avl),      ''),
                    acy_cur_raw      = NULLIF(TRIM(@acy_cur),      ''),
                    lcy_cur_raw      = NULLIF(TRIM(@lcy_cur),      ''),
                    dr_gl            = NULLIF(TRIM(@dr_gl),        ''),
                    cr_gl            = NULLIF(TRIM(@cr_gl),        ''),
                    raw              = {$rawExpr},
                    created_at       = '{$now}',
                    updated_at       = '{$now}'
            ");
            $lap('load_data_infile_ms');

            // ── 4. Single transaction: delete stale → insert rejects → insert main ─
            //
            //    Why this order?
            //    - Delete first so a retry of the same file starts clean.
            //    - Rejects before main so ROW_COUNT() from each INSERT gives us
            //      the exact counts without an extra COUNT(*) scan.
            DB::beginTransaction();

            // Remove any previous import of this file (re-import / retry scenario)
            DB::table('customer_balances')
                ->where('uploaded_file_id', $uploadedFileId)
                ->delete();
            $lap('delete_previous_rows_ms');

            // Reject rows missing any mandatory key — single INSERT, no COUNT(*) needed
            DB::statement("
                INSERT INTO customer_balance_rejects
                    (uploaded_file_id, balance_date, reason, raw, created_at)
                SELECT
                    uploaded_file_id,
                    balance_date,
                    'missing_keys',
                    raw,
                    ?
                FROM `{$stageName}`
                WHERE cust_ac_no IS NULL
                   OR currency   IS NULL
                   OR branch_code IS NULL
            ", [$now]);

            // ROW_COUNT() immediately after INSERT gives us rejected-row count
            $skipped = (int) DB::selectOne('SELECT ROW_COUNT() AS c')->c;
            $lap('reject_missing_keys_ms');

            // Compute every candidate row once (acy_balance / lcy_balance included)
            // as a derived table shared by both inserts below: rows with a
            // non-negative balance in *both* currencies go to customer_balances;
            // anything negative in either is logged to customer_balance_rejects
            // (reason 'negative_balance') instead of being silently discarded —
            // this is what keeps customer_balances from accumulating rows
            // nobody reports off, while still leaving an audit trail.
            $rawSelect = $keepRaw ? 's.raw' : 'NULL';

            $computedSql = "
                SELECT
                    s.uploaded_file_id,
                    s.cust_ac_no,
                    s.currency,
                    'ACY' AS currency_type,
                    s.branch_code,
                    s.cif,
                    s.ac_desc   AS customer_name,
                    s.ac_desc   AS account_desc,
                    CAST(NULL AS UNSIGNED) AS cif_profile_id,
                    s.balance_date,

                    /*
                     * balance / acy_balance
                     * Prefer acy_cur_raw (current balance), fall back to acy_avl_raw
                     * (available balance).  Strip commas and spaces before casting.
                     */
                    CAST(
                        REPLACE(REPLACE(
                            COALESCE(
                                NULLIF(TRIM(s.acy_cur_raw), ''),
                                NULLIF(TRIM(s.acy_avl_raw), ''),
                                '0'
                            ),
                        ',', ''), ' ', '')
                    AS DECIMAL(20, 2)) AS balance,

                    CAST(
                        REPLACE(REPLACE(
                            COALESCE(
                                NULLIF(TRIM(s.acy_cur_raw), ''),
                                NULLIF(TRIM(s.acy_avl_raw), ''),
                                '0'
                            ),
                        ',', ''), ' ', '')
                    AS DECIMAL(20, 2)) AS acy_balance,

                    /*
                     * lcy_balance
                     * Use the explicit LCY column when present; for KES accounts
                     * the ACY value IS the local value; everything else defaults to 0.
                     */
                    CAST(
                        REPLACE(REPLACE(
                            CASE
                                WHEN NULLIF(TRIM(s.lcy_cur_raw), '') IS NOT NULL
                                    THEN TRIM(s.lcy_cur_raw)
                                WHEN UPPER(TRIM(s.currency)) = 'KES'
                                    THEN COALESCE(
                                            NULLIF(TRIM(s.acy_cur_raw), ''),
                                            NULLIF(TRIM(s.acy_avl_raw), ''),
                                            '0'
                                         )
                                ELSE '0'
                            END,
                        ',', ''), ' ', '')
                    AS DECIMAL(20, 2)) AS lcy_balance,

                    s.dr_gl,
                    s.cr_gl,
                    {$rawSelect} AS raw,
                    '{$now}' AS created_at,
                    '{$now}' AS updated_at
                FROM `{$stageName}` s
                WHERE s.cust_ac_no   IS NOT NULL
                  AND s.currency     IS NOT NULL
                  AND s.branch_code  IS NOT NULL
            ";

            // Materialize the computed candidates ONCE — the two inserts below
            // both read from this table instead of each re-evaluating the
            // CAST/REPLACE/COALESCE expressions above over the staging table.
            $computedStageName = 'tmp_bal_computed_' . getmypid() . '_' . substr(md5(uniqid('', true)), 0, 8);

            DB::statement("CREATE TEMPORARY TABLE `{$computedStageName}` AS {$computedSql}");
            $lap('compute_candidates_ms');

            DB::statement("
                INSERT INTO customer_balances
                (
                    uploaded_file_id, cust_ac_no, currency, currency_type,
                    branch_code, cif, customer_name, account_desc,
                    cif_profile_id, balance_date,
                    balance, acy_balance, lcy_balance,
                    dr_gl, cr_gl, raw,
                    created_at, updated_at
                )
                SELECT
                    uploaded_file_id, cust_ac_no, currency, currency_type,
                    branch_code, cif, customer_name, account_desc,
                    cif_profile_id, balance_date,
                    balance, acy_balance, lcy_balance,
                    dr_gl, cr_gl, raw,
                    created_at, updated_at
                FROM `{$computedStageName}` computed
                WHERE computed.acy_balance >= 0
                  AND computed.lcy_balance >= 0
            ");

            $inserted = (int) DB::selectOne('SELECT ROW_COUNT() AS c')->c;
            $lap('insert_customer_balances_ms');

            // Log negative-balance rows to the rejects table (same shape as the
            // missing_keys rejects above) instead of just dropping them.
            DB::statement("
                INSERT INTO customer_balance_rejects
                    (uploaded_file_id, balance_date, reason, raw, created_at)
                SELECT
                    uploaded_file_id,
                    balance_date,
                    'negative_balance',
                    raw,
                    '{$now}'
                FROM `{$computedStageName}` computed
                WHERE computed.acy_balance < 0
                   OR computed.lcy_balance < 0
            ");

            $skipped += (int) DB::selectOne('SELECT ROW_COUNT() AS c')->c;
            $lap('reject_negative_balance_ms');

            DB::table('uploaded_files')
                ->where('id', $uploadedFileId)
                ->update(['status' => 'imported', 'error' => null, 'updated_at' => now()]);

            DB::commit();
            $lap('commit_ms');

            $timings['total_ms'] = round((microtime(true) - $importT0) * 1000);
            Log::info('balances import timing', [
                'file'     => basename($filePath),
                'inserted' => $inserted,
                'skipped'  => $skipped,
            ] + $timings);

            return [$inserted, $skipped, $timings];

        } catch (\Throwable $e) {
            try { DB::rollBack(); } catch (\Throwable) {}

            DB::table('uploaded_files')
                ->where('id', $uploadedFileId)
                ->update([
                    'status'     => 'failed',
                    'error'      => Str::limit($e->getMessage(), 1000),
                    'updated_at' => now(),
                ]);

            $timings['total_ms'] = round((microtime(true) - $importT0) * 1000);
            Log::error('balances import timing (failed)', [
                'file'  => basename($filePath),
                'error' => $e->getMessage(),
            ] + $timings);

            throw $e;

        } finally {
            // Always clean up — even on exception
            if ($tmp && is_file($tmp)) {
                @unlink($tmp);
            }
            // Temp tables drop with the connection, but explicit drop is cleaner
            // when the same long-lived worker connection handles many files.
            if ($computedStageName) {
                try { DB::statement("DROP TEMPORARY TABLE IF EXISTS `{$computedStageName}`"); } catch (\Throwable) {}
            }
            if ($stageName) {
                try { DB::statement("DROP TEMPORARY TABLE IF EXISTS `{$stageName}`"); } catch (\Throwable) {}
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // File preprocessing
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Stream the raw Flexcube file into a clean, tab-delimited temp file ready
     * for LOAD DATA LOCAL INFILE.
     *
     * Removes:
     *   - "BALANCES PER CUSTOMER" title lines
     *   - "Branch Code …" header lines
     *   - Blank lines
     *
     * Handles:
     *   - Multi-line records (continuation lines that start with a tab, or
     *     wrapped description text without a tab)
     *   - Incomplete records (no currency column yet) → held in buffer
     *   - New record displacing an incomplete buffer → old buffer discarded
     */
    private function makeLoadReadyFile(string $filePath): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'bal_load_');

        if ($tmp === false) {
            throw new \RuntimeException('Could not create temp file in: ' . sys_get_temp_dir());
        }

        $out = fopen($tmp, 'wb');

        if ($out === false) {
            throw new \RuntimeException("Could not open temp file for writing: {$tmp}");
        }

        $in     = new SplFileObject($filePath);
        $buffer = '';

        while (!$in->eof()) {
            $line = rtrim((string) $in->fgets(), "\r\n");

            // ── Fast-reject empty/header lines ───────────────────────────────────
            if ($line === '') {
                continue;
            }

            // strpos is faster than str_contains on very hot paths
            if (strpos($line, 'BALANCES PER CUSTOMER') !== false) {
                continue;
            }

            if (str_starts_with($line, 'Branch Code')) {
                continue;
            }

            // ── Buffer management ─────────────────────────────────────────────────
            if ($buffer === '') {
                $buffer = $line;
            } elseif (!str_starts_with($line, "\t") && preg_match(self::ROW_START_RE, $line)) {
                // A new valid record starts while we still have an incomplete buffer.
                // The previous buffer was a broken/truncated record — discard it and
                // start fresh with the new line.
                $buffer = $line;
            } else {
                // Continuation line:
                //   starts with \t  → next column(s) in the same record
                //   no \t           → description text wrapped to the next physical line
                $buffer .= str_starts_with($line, "\t")
                    ? $line
                    : (' ' . ltrim($line));
            }

            // ── Attempt to produce a complete 10-column row from the buffer ───────
            $normalized = $this->normalizeRow($buffer);

            if ($normalized !== null) {
                fwrite($out, $normalized . "\n");
                $buffer = '';
            }
        }

        // Any leftover incomplete buffer is intentionally dropped (broken record).

        fclose($out);

        return $tmp;
    }

    /**
     * Try to extract a clean 10-column tab-delimited line from the buffer.
     *
     * Column layout:
     *   0  Branch code
     *   1  Cust Ac No
     *   2  CIF
     *   3  Account description  (may span multiple original tab-columns)
     *   4  Currency             (3-letter ISO code — used as anchor)
     *   5  ACY Available Balance
     *   6  ACY Current Balance
     *   7  LCY Current Balance
     *   8  Dr GL
     *   9  Cr GL
     *
     * Returns null if the buffer does not yet contain a complete record.
     */
    private function normalizeRow(string $buffer): ?string
    {
        $parts = explode("\t", $buffer);
        $count = count($parts);

        // Minimum viable: branch + acno + cif + at least one desc piece = 4 cols
        if ($count < 4) {
            return null;
        }

        // ── Locate the currency anchor ────────────────────────────────────────────
        // Currency always appears after the description block, which starts at
        // index 3.  Scan forward until we find a 3-letter alpha token.
        $currencyIndex = null;

        for ($i = 4; $i < $count; $i++) {
            $tok = strtoupper(trim($parts[$i]));
            if ($tok !== '' && preg_match(self::CURRENCY_RE, $tok)) {
                $currencyIndex = $i;
                break;
            }
        }

        if ($currencyIndex === null) {
            return null; // currency column not yet in buffer — need more lines
        }

        // ── Verify we have all tail columns ───────────────────────────────────────
        $remaining = $count - ($currencyIndex + 1);

        if ($remaining < self::TAIL_COLS) {
            return null; // still waiting for tail columns
        }

        // ── Extract fields ────────────────────────────────────────────────────────
        $branch = trim($parts[0]);
        $acno   = trim($parts[1]);
        $cif    = trim($parts[2]);

        // Everything between index 3 and the currency column is the description.
        // Flatten whitespace so we don't end up with double-spaces from wrapping.
        $descParts = array_slice($parts, 3, max(0, $currencyIndex - 3));
        $desc      = trim(preg_replace('/\s+/', ' ', implode(' ', array_map('trim', $descParts))));

        $ccy  = strtoupper(trim($parts[$currencyIndex]));
        $tail = array_pad(
            array_slice($parts, $currencyIndex + 1, self::TAIL_COLS),
            self::TAIL_COLS,
            ''
        );

        [$acyAvl, $acyCur, $lcyCur, $drGl, $crGl] = array_map('trim', $tail);

        // ── Basic sanity check ────────────────────────────────────────────────────
        // A row without branch, account number, or currency can't be inserted usefully.
        if ($branch === '' || $acno === '' || $ccy === '') {
            return null;
        }

        return implode("\t", [$branch, $acno, $cif, $desc, $ccy, $acyAvl, $acyCur, $lcyCur, $drGl, $crGl]);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Utilities
    // ─────────────────────────────────────────────────────────────────────────────

    private function sha256(string $path): string
    {
        return @hash_file('sha256', $path) ?: '';
    }

    /**
     * Extract a Y-m-d date from either the filename (DD.MM.YYYY) or
     * directory path (/YYYY/Mon/D/).  Falls back to today.
     */
    private function parseFileDate(string $filePath): string
    {
        $name = basename($filePath);

        if (preg_match(self::DATE_NAME_RE, $name, $m)) {
            return Carbon::createFromDate((int) $m[3], (int) $m[2], (int) $m[1])->format('Y-m-d');
        }

        $normalized = str_replace('\\', '/', $filePath);

        if (preg_match(self::DATE_PATH_RE, $normalized, $m)) {
            return Carbon::parse("{$m[3]} {$m[2]} {$m[1]}")->format('Y-m-d');
        }

        return now()->toDateString();
    }
}
