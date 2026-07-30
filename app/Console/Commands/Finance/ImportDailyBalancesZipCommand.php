<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Mail\BalancesImportStatusMail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Waits for the previous business day's Flexcube balances zip/folder to land
 * (Flexcube produces Friday's file on Monday, so we never target "today" — we
 * target the last business day before today), imports it once found, then
 * triggers reports:run-balances. Scheduled once at 07:30 (Africa/Nairobi);
 * retries internally every --retry-minutes up to --max-attempts total attempts.
 *
 * The month folder on the share is matched case-insensitively (Jul/JUL/jul), and
 * within it we always use the latest numeric day folder present — it only counts
 * as "found" if that latest day matches the target date, otherwise we keep waiting.
 */
class ImportDailyBalancesZipCommand extends Command
{
    protected $signature = 'finance:import-daily-balances
        {--date= : Explicit date to import, YYYY-MM-DD (overrides the previous-business-day default; use for manual/backfill runs)}
        {--max-attempts=4 : Total attempts before giving up}
        {--retry-minutes=30 : Minutes to wait between attempts}';

    protected $description = "Wait for the previous business day's balances zip to appear, import it, then run reports:run-balances (emails progress along the way).";

    private const TIMEZONE = 'Africa/Nairobi';

    public function handle(): int
    {
        $dateOpt = trim((string) ($this->option('date') ?? ''));
        $date = $dateOpt !== '' ? Carbon::parse($dateOpt) : $this->previousBusinessDay(now()->timezone(self::TIMEZONE));
        $dateLabel = $date->toDateString();

        $maxAttempts = max(1, (int) $this->option('max-attempts'));
        $retryMinutes = max(1, (int) $this->option('retry-minutes'));

        $this->info("Balances import: watching for {$dateLabel} (up to {$maxAttempts} attempt(s), {$retryMinutes} min apart).");

        $this->notify('info', 'Import started', "Started looking for the balances file for {$dateLabel}.", [
            'Date' => $dateLabel,
            'Max attempts' => (string) $maxAttempts,
        ]);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $path = $this->resolveTodayPath($date);

            if ($path === null) {
                $this->warn("Attempt {$attempt}/{$maxAttempts}: balances file for {$dateLabel} not found.");

                if ($attempt === $maxAttempts) {
                    $this->notify('error', 'File not found', "The balances file for {$dateLabel} never appeared after {$maxAttempts} attempt(s). Giving up for today — please import manually once the file lands.", [
                        'Date' => $dateLabel,
                    ]);
                    return self::FAILURE;
                }

                $nextRetry = now()->timezone(self::TIMEZONE)->addMinutes($retryMinutes)->format('H:i');
                $this->notify('warning', 'File not found, retrying', "The balances file for {$dateLabel} isn't there yet. Will retry (attempt " . ($attempt + 1) . "/{$maxAttempts}) at {$nextRetry}.", [
                    'Date' => $dateLabel,
                ]);

                sleep($retryMinutes * 60);
                continue;
            }

            $this->info("Attempt {$attempt}/{$maxAttempts}: found {$path}");

            try {
                $exit = Artisan::call('import:balances', ['path' => $path], $this->output);
            } catch (Throwable $e) {
                $exit = self::FAILURE;
                $this->error('import:balances threw: ' . $e->getMessage());
            }

            if ($exit === self::SUCCESS) {
                $this->notify('success', 'Import successful', "Balances for {$dateLabel} were imported successfully. Starting the top movers report run now.", [
                    'Date' => $dateLabel,
                    'Source' => $path,
                    'Attempt' => "{$attempt}/{$maxAttempts}",
                ]);

                $this->info('Import succeeded. Running reports:run-balances...');

                Artisan::call('reports:run-balances', [
                    '--auto' => true,
                    '--date' => $dateLabel,
                    '--no-import' => true,
                ], $this->output);

                $this->info('Pruning old balance rows...');
                Artisan::call('finance:prune-balances', [], $this->output);

                $this->info('Rebuilding RM workload summary...');
                Artisan::call('finance:build-rm-workload', [], $this->output);

                return self::SUCCESS;
            }

            $this->error("import:balances failed for {$path} (exit code {$exit}).");

            if ($attempt === $maxAttempts) {
                $this->notify('error', 'Import failed', "Found the balances file for {$dateLabel} but the import failed after attempt {$attempt}/{$maxAttempts}. Check storage/logs and the file for corruption.", [
                    'Date' => $dateLabel,
                    'Source' => $path,
                ]);
                return self::FAILURE;
            }

            $nextRetry = now()->timezone(self::TIMEZONE)->addMinutes($retryMinutes)->format('H:i');
            $this->notify('warning', 'Import failed, retrying', "The import failed for {$dateLabel}. Will retry (attempt " . ($attempt + 1) . "/{$maxAttempts}) at {$nextRetry}.", [
                'Date' => $dateLabel,
                'Source' => $path,
            ]);

            sleep($retryMinutes * 60);
        }

        return self::FAILURE;
    }

    /**
     * The day before $date, rolled back over the weekend if that lands on Sat/Sun.
     * E.g. from a Monday this returns the preceding Friday; from Tue-Fri it's just yesterday.
     */
    private function previousBusinessDay(Carbon $date): Carbon
    {
        $target = $date->copy()->subDay();

        while ($target->isWeekend()) {
            $target->subDay();
        }

        return $target;
    }

    /**
     * Resolves the target date's balances zip/folder path under base_dir/{year}/{month}/{day}/{country}[.zip].
     * Month is matched case-insensitively; day always uses the latest numeric folder present,
     * and only counts as a match if it equals the target's calendar day.
     */
    private function resolveTodayPath(Carbon $date): ?string
    {
        $baseDir = rtrim((string) config('reports.balances.base_dir'), '/\\');
        $country = trim((string) config('reports.balances.country_folder', 'Kenya'));

        $yearDir = $baseDir . '/' . $date->format('Y');
        if (!is_dir($yearDir)) {
            return null;
        }

        $monthEntry = $this->findCaseInsensitiveDirEntry($yearDir, $date->format('M'));
        if ($monthEntry === null) {
            return null;
        }

        $monthDir = $yearDir . '/' . $monthEntry;

        $latestDay = $this->findLatestNumericDirEntry($monthDir);
        if ($latestDay === null || (int) $latestDay !== (int) $date->format('j')) {
            return null;
        }

        $dayDir = $monthDir . '/' . $latestDay;
        $zipPath = $dayDir . '/' . $country . '.zip';
        $folderPath = $dayDir . '/' . $country;

        if (file_exists($zipPath)) {
            return $zipPath;
        }

        if (is_dir($folderPath)) {
            return $folderPath;
        }

        return null;
    }

    private function findCaseInsensitiveDirEntry(string $dir, string $target): ?string
    {
        $entries = @scandir($dir);
        if ($entries === false) {
            return null;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (!is_dir($dir . DIRECTORY_SEPARATOR . $entry)) {
                continue;
            }
            if (strcasecmp($entry, $target) === 0) {
                return $entry;
            }
        }

        return null;
    }

    private function findLatestNumericDirEntry(string $dir): ?string
    {
        $entries = @scandir($dir);
        if ($entries === false) {
            return null;
        }

        $best = null;
        $bestVal = -1;

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (!ctype_digit($entry)) {
                continue;
            }
            if (!is_dir($dir . DIRECTORY_SEPARATOR . $entry)) {
                continue;
            }

            $val = (int) $entry;
            if ($val > $bestVal) {
                $bestVal = $val;
                $best = $entry;
            }
        }

        return $best;
    }

    private function notify(string $level, string $heading, string $message, array $details = []): void
    {
        try {
            Mail::send(new BalancesImportStatusMail($level, $heading, $message, $details));
        } catch (Throwable $e) {
            $this->error('Failed to send notification email: ' . $e->getMessage());
        }
    }
}
