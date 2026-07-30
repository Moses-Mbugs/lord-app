<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Services\Finance\BalanceFileImporter;
use Illuminate\Console\Command;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\Process;

/**
 * Orchestrates balance file imports, fanning out to N parallel worker processes.
 *
 * Each worker spawns its own DB connection and creates a private, per-connection
 * TEMPORARY TABLE — no shared staging table, no cross-worker locking.
 *
 * Usage:
 *   php artisan import:balances /data/balances             # 4 workers (default)
 *   php artisan import:balances /data/balances --workers=8
 *   php artisan import:balances /data/balances --workers=1  # sequential
 *
 * Requires Laravel 10+ (Illuminate\Process\Pool).
 */
class ImportBalancesCommand extends Command
{
    protected $signature = 'import:balances
        {path : Directory path (or .zip file) containing Flexcube balance txt files}
        {--keep-raw=0    : Store raw line (slower + bigger DB)}
        {--workers=4     : Number of parallel worker processes (1 = sequential)}
        {--force         : Re-import even if the file checksum is already marked as imported}
        {--file-list=    : Internal: newline-delimited list of files for this worker (do not use manually)}';

    protected $description = 'FAST parallel import of customer balances (per-connection temp tables + LOAD DATA LOCAL INFILE).';

    /** How often (in source lines) a worker prints a plain-text progress line in parallel mode. */
    private const WORKER_PROGRESS_TICK_LINES = 20000;

    public function __construct(private readonly BalanceFileImporter $importer)
    {
        parent::__construct();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Entry point
    // ─────────────────────────────────────────────────────────────────────────────

    public function handle(): int
    {
        // Internal worker mode — spawned by the parallel orchestrator below.
        if ($fileList = $this->option('file-list')) {
            return $this->runAsWorker($fileList);
        }

        $path    = (string) $this->argument('path');
        $keepRaw = (bool)((int) $this->option('keep-raw'));
        $force   = (bool) $this->option('force');
        $workers = max(1, (int) $this->option('workers'));

        $tempDir = null;

        try {
            $dir = $this->resolvePath($path, $tempDir);
            if ($dir === null) {
                return self::FAILURE;
            }

            $this->info("Importing balances from: {$dir}");

            if (!is_dir($dir)) {
                $this->error("Path is not a directory: {$dir}");
                return self::FAILURE;
            }

            $files = $this->findBalanceFiles($dir);

            if (empty($files)) {
                $this->warn("No 'BALANCES PER CUSTOMER *.txt' files found in: {$dir}");
                $this->printSummary(0, 0, 0);
                return self::SUCCESS;
            }

            $effectiveWorkers = min($workers, count($files));
            $this->info(sprintf('Found %d file(s). Spawning %d worker(s).', count($files), $effectiveWorkers));
            $this->newLine();

            return $effectiveWorkers === 1
                ? $this->runSequential($files, $keepRaw, $force)
                : $this->runParallel($files, $keepRaw, $effectiveWorkers, $force);
        } finally {
            if ($tempDir !== null && is_dir($tempDir)) {
                $this->deleteTempDir($tempDir);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Sequential mode  (--workers=1)
    // ─────────────────────────────────────────────────────────────────────────────

    private function runSequential(array $files, bool $keepRaw, bool $force = false): int
    {
        $fileBar = $this->output->createProgressBar(count($files));
        $fileBar->setFormat(' File %current%/%max% [%bar%] %percent:3s%%');
        $fileBar->start();
        $this->newLine();

        $filesProcessed = $inserted = $skipped = 0;

        foreach ($files as $filePath) {
            $filesProcessed++;
            $this->line('Importing: ' . basename($filePath));

            $subBar = $this->output->createProgressBar(100);
            $subBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %message%');
            $subBar->setMessage('Starting…');
            $subBar->start();

            try {
                [$ins, $sk, $timings] = $this->importer->import($filePath, $keepRaw, $force);

                $subBar->finish();
                $this->newLine();

                $inserted += $ins;
                $skipped  += $sk;
                $this->line('  ✔ ' . basename($filePath) . "  inserted={$ins}, skipped={$sk}");
                $this->printTimings($timings);
            } catch (\Throwable $e) {
                $subBar->finish();
                $this->newLine();
                $this->error('  ✖ ' . basename($filePath) . ': ' . $e->getMessage());
            }

            $this->newLine();
            $fileBar->advance();
        }

        $fileBar->finish();
        $this->newLine(2);

        $this->printSummary($filesProcessed, $inserted, $skipped);
        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Parallel mode  (--workers=N, N > 1)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Splits files into N roughly-equal chunks, writes each chunk to a temp list
     * file, then spawns N child artisan processes (via Process::pool) each
     * handling their own chunk.  Workers emit a final "RESULT:{json}" line that
     * we parse to accumulate the aggregate totals, plus periodic plain-text
     * "[worker-N] line X/Y" progress lines while running.
     */
    private function runParallel(array $files, bool $keepRaw, int $workers, bool $force = false): int
    {
        $chunks    = array_chunk($files, (int) ceil(count($files) / $workers));
        $listFiles = [];

        foreach ($chunks as $i => $chunk) {
            $tmp = tempnam(sys_get_temp_dir(), "bal_list_{$i}_");
            file_put_contents($tmp, implode("\n", $chunk));
            $listFiles[] = $tmp;
        }

        $results = [];

        try {
            Process::pool(function (Pool $pool) use ($listFiles, $keepRaw, $force): void {
                foreach ($listFiles as $i => $listFile) {
                    $cmd = [
                        PHP_BINARY,
                        base_path('artisan'),
                        'import:balances',
                        $this->argument('path'), // required argument; unused in worker mode
                        '--keep-raw=' . ($keepRaw ? '1' : '0'),
                        '--file-list=' . $listFile,
                    ];

                    if ($force) {
                        $cmd[] = '--force';
                    }

                    $pool->as("worker-{$i}")->timeout(0)->command($cmd);
                }
            })->start(function (string $type, string $output, string $key) use (&$results): void {
                foreach (explode("\n", trim($output)) as $line) {
                    $line = trim($line);

                    if ($line === '') {
                        continue;
                    }

                    if (str_starts_with($line, 'RESULT:')) {
                        // Machine-readable summary from worker
                        $decoded = json_decode(substr($line, 7), true);
                        if (is_array($decoded)) {
                            $decoded['_worker'] = $key;
                            $results[] = $decoded;
                        }
                        continue;
                    }

                    // Pass human-readable lines through prefixed with worker key
                    // (this includes the "[worker-N] line X/Y" progress lines
                    // emitted by runAsWorker()'s progress callback below).
                    $type === 'err'
                        ? $this->error("  [{$key}] {$line}")
                        : $this->line("  [{$key}] {$line}");
                }
            })->wait();
        } finally {
            foreach ($listFiles as $f) {
                @unlink($f);
            }
        }

        $expectedWorkers = count($listFiles);
        $reportedWorkers = count(array_unique(array_column($results, '_worker')));

        if ($reportedWorkers < $expectedWorkers) {
            $this->newLine();
            $this->error(sprintf(
                'Only %d of %d worker(s) reported a result — some chunks may have failed silently. Check worker output above.',
                $reportedWorkers,
                $expectedWorkers
            ));
        }

        $this->newLine();
        $this->printSummary(
            array_sum(array_column($results, 'files')),
            array_sum(array_column($results, 'inserted')),
            array_sum(array_column($results, 'skipped')),
        );

        return $reportedWorkers < $expectedWorkers ? self::FAILURE : self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Worker mode  (internal — called by runParallel via Process::pool)
    // ─────────────────────────────────────────────────────────────────────────────

    private function runAsWorker(string $fileListPath): int
    {
        if (!file_exists($fileListPath)) {
            $this->error("File list not found: {$fileListPath}");
            return self::FAILURE;
        }

        $keepRaw = (bool)((int) $this->option('keep-raw'));
        $force   = (bool) $this->option('force');
        $files   = array_filter(
            array_map('trim', explode("\n", (string) file_get_contents($fileListPath))),
            static fn(string $f): bool => $f !== ''
        );

        $filesProcessed = $inserted = $skipped = 0;

        foreach ($files as $filePath) {
            $filesProcessed++;

            // In worker mode there's no live progress bar (output is piped through
            // the parent's pool handler), so we print periodic plain-text lines
            // instead — cheap, and readable when interleaved with other workers.
            $progress = function (string $stage, int $current = 0, int $total = 0) use ($filePath): void {
                if ($stage === 'preprocessing' && $current % self::WORKER_PROGRESS_TICK_LINES === 0) {
                    $this->line(sprintf(
                        '%s: line %d%s',
                        basename($filePath),
                        $current,
                        $total ? "/{$total}" : ''
                    ));
                } elseif ($stage === 'loading') {
                    $this->line(basename($filePath) . ': loading into staging table…');
                } elseif ($stage === 'inserting') {
                    $this->line(basename($filePath) . ': inserting into customer_balances…');
                }
            };

            try {
                [$ins, $sk, $timings] = $this->importer->import($filePath, $keepRaw, $force);
                $inserted += $ins;
                $skipped  += $sk;
                $this->line('  ✔ ' . basename($filePath) . "  inserted={$ins}, skipped={$sk}");
                $this->printTimings($timings, basename($filePath) . ': ');
            } catch (\Throwable $e) {
                $this->error('  ✖ ' . basename($filePath) . ': ' . $e->getMessage());
            }
        }

        // Machine-readable line parsed by the orchestrator's output handler
        $this->line('RESULT:' . json_encode([
            'files'    => $filesProcessed,
            'inserted' => $inserted,
            'skipped'  => $skipped,
        ]));

        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Resolves the user-supplied path to a directory of balance txt files.
     *
     * Given a path like  Y:\JUN\22\Kenya.zip :
     *   1. Zip exists          → extract the balance folder from it (sets $tempDir).
     *   2. Zip missing, but a folder named "Kenya" exists → use that folder directly.
     *   3. Neither exists      → error.
     *
     * Given a plain directory path → use it directly.
     */
    private function resolvePath(string $path, ?string &$tempDir): ?string
    {
        if (!str_ends_with(strtolower($path), '.zip')) {
            // Plain directory — may be on a network mount (e.g. the CIFS share balance
            // files land on); copy matching files to local disk first so parsing/
            // checksums never touch a flaky network share mid-file.
            return $this->copyBalancesFolderToLocal($path, $tempDir);
        }

        // Zip path supplied.
        if (file_exists($path)) {
            return $this->extractBalancesFromZip($path, $tempDir);
        }

        // Zip missing — fall back to a same-named folder (strip .zip).
        $folderPath = substr($path, 0, -4);
        if (is_dir($folderPath)) {
            $this->warn("Zip not found; falling back to folder: {$folderPath}");
            return $this->copyBalancesFolderToLocal($folderPath, $tempDir);
        }

        $this->error("Neither zip file '{$path}' nor folder '" . basename($folderPath) . "' found.");
        return null;
    }

    /**
     * Copies the "BALANCES PER CUSTOMER *.txt" files from $sourceDir (which may be a
     * network mount) to a local temp dir, so every subsequent read (checksum,
     * preprocessing) hits local disk instead of the network share.
     */
    private function copyBalancesFolderToLocal(string $sourceDir, ?string &$tempDir): ?string
    {
        if (!is_dir($sourceDir)) {
            $this->error("Path is not a directory: {$sourceDir}");
            return null;
        }

        $destDir = storage_path('app/tmp/bal_local_' . date('YmdHis'));
        @mkdir($destDir, 0755, true);

        $this->info("Copying balance files from network path to local disk: {$destDir}");

        $files = $this->findBalanceFiles($sourceDir);
        $copied = 0;

        foreach ($files as $filePath) {
            $this->copyWithRetry($filePath, $destDir . DIRECTORY_SEPARATOR . basename($filePath));
            $copied++;
        }

        $this->info("Copied {$copied} file(s) to local disk.");

        $tempDir = $destDir;

        return $destDir;
    }

    /**
     * The source may live on a network share that intermittently stalls (e.g. CIFS
     * reconnects); retry a failed copy a few times before giving up, so one transient
     * hiccup doesn't fail the whole import.
     */
    private function copyWithRetry(string $source, string $dest, int $maxAttempts = 3): void
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            if (@copy($source, $dest)) {
                return;
            }

            if ($attempt === $maxAttempts) {
                throw new \RuntimeException("Failed to copy balance file from network path after {$maxAttempts} attempt(s): {$source}");
            }

            sleep(5);
        }
    }

    /**
     * Opens a zip, sniffs for whichever internal folder contains the
     * "BALANCES PER CUSTOMER *.txt" files, extracts only those files
     * to a fresh temp dir under storage/app/tmp/, and sets $tempDir
     * so the caller can clean up afterward.
     */
    private function extractBalancesFromZip(string $zipPath, ?string &$tempDir): ?string
    {
        if (!file_exists($zipPath)) {
            $this->error("Zip file not found: {$zipPath}");
            return null;
        }

        // The zip may live on a network share that intermittently stalls (e.g. CIFS
        // reconnects); copy it to local disk first so ZipArchive's many small seeks/
        // reads during extraction never touch the network mount directly.
        $localZipPath = storage_path('app/tmp/bal_zip_' . date('YmdHis') . '_' . substr(md5(uniqid('', true)), 0, 8) . '.zip');
        @mkdir(dirname($localZipPath), 0755, true);
        $this->copyWithRetry($zipPath, $localZipPath);

        try {
            $zip = new \ZipArchive();
            if ($zip->open($localZipPath) !== true) {
                $this->error("Could not open zip file: {$zipPath}");
                return null;
            }

            // Sniff: find which internal folder holds the balance txt files.
            $targetPrefix = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry    = $zip->getNameIndex($i);
                $basename = basename($entry);

                if (
                    str_ends_with(strtolower($basename), '.txt') &&
                    str_contains(strtolower($basename), 'balances per customer')
                ) {
                    $folder = dirname($entry);
                    $targetPrefix = ($folder === '.' || $folder === '') ? '' : rtrim($folder, '/') . '/';
                    break;
                }
            }

            if ($targetPrefix === null) {
                $zip->close();
                $this->error("No 'BALANCES PER CUSTOMER *.txt' files found inside zip: {$zipPath}");
                return null;
            }

            $label   = $targetPrefix === '' ? 'zip root' : rtrim($targetPrefix, '/');
            $destDir = storage_path('app/tmp/bal_extract_' . date('YmdHis'));
            @mkdir($destDir, 0755, true);

            $this->info("Found balance files in '{$label}'. Extracting → {$destDir}");

            $extracted = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry    = $zip->getNameIndex($i);
                $basename = basename($entry);

                // Skip directory entries and files outside the target folder.
                if ($basename === '' || str_ends_with($entry, '/')) {
                    continue;
                }
                if ($targetPrefix !== '' && !str_starts_with($entry, $targetPrefix)) {
                    continue;
                }

                file_put_contents($destDir . DIRECTORY_SEPARATOR . $basename, $zip->getFromIndex($i));
                $extracted++;
            }

            $zip->close();
            $this->info("Extracted {$extracted} file(s) from zip.");

            $tempDir = $destDir;
            return $destDir;
        } finally {
            @unlink($localZipPath);
        }
    }

    private function deleteTempDir(string $dir): void
    {
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            @unlink($dir . DIRECTORY_SEPARATOR . $entry);
        }
        @rmdir($dir);
    }

    private function findBalanceFiles(string $dir): array
    {
        $out = [];

        $entries = @scandir($dir);

        if ($entries === false) {
            $this->error("Could not read directory (check permissions): {$dir}");
            return $out;
        }

        foreach ($entries as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }

            $path = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;

            if (!is_file($path)) continue;
            if (!str_ends_with(strtolower($name), '.txt')) continue;
            if (!str_contains(strtolower($name), 'balances per customer')) continue;

            $out[] = $path;
        }

        sort($out);
        return $out;
    }

    private function printSummary(int $files, int $inserted, int $skipped): void
    {
        $this->line('Balances import complete.');
        $this->line("Files processed : {$files}");
        $this->line("Rows inserted   : {$inserted}");
        $this->line("Rows skipped    : {$skipped}");
    }

    /**
     * Prints the per-phase timing breakdown returned by BalanceFileImporter::import()
     * so a slow run shows exactly which phase ate the time (also logged to
     * storage/logs/laravel.log via BalanceFileImporter for scheduled/cron runs).
     */
    private function printTimings(array $timings, string $prefix = ''): void
    {
        foreach ($timings as $label => $ms) {
            $this->line(sprintf('    %s%-30s %8s ms', $prefix, $label, number_format($ms)));
        }
    }
}
