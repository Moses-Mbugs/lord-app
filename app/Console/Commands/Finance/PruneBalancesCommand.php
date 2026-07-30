<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Keeps customer_balances from growing forever: daily granularity is only
 * useful for the trailing window that reports:run-balances actually diffs
 * against (yesterday vs today), so anything older than --days collapses
 * down to one row per account per month — the last balance_date Flexcube
 * actually posted for that month.
 */
class PruneBalancesCommand extends Command
{
    protected $signature = 'finance:prune-balances
        {--days=14 : Keep full daily detail for this many trailing calendar days}
        {--batch=5000 : Rows deleted per DELETE statement (keeps locks/undo short)}
        {--dry-run : Report what would be deleted without deleting anything}';

    protected $description = 'Deletes customer_balances rows older than --days, keeping only each month\'s last imported balance_date.';

    public function handle(): int
    {
        $days  = max(1, (int) $this->option('days'));
        $batch = max(100, (int) $this->option('batch'));
        $dry   = (bool) $this->option('dry-run');

        $cutoff = now()->subDays($days)->startOfDay()->toDateString();

        $keepDates = DB::table('customer_balances')
            ->where('balance_date', '<', $cutoff)
            ->selectRaw('MAX(balance_date) as keep_date')
            ->groupBy(DB::raw('YEAR(balance_date), MONTH(balance_date)'))
            ->pluck('keep_date')
            ->map(fn ($d) => (string) $d)
            ->all();

        $this->info("Cutoff: {$cutoff} (keeping {$days} day(s) of daily detail).");
        $this->info($keepDates
            ? 'Month-end dates kept for anything older: ' . implode(', ', $keepDates)
            : 'No rows older than the cutoff — nothing to collapse to month-end.');

        $candidates = DB::table('customer_balances')->where('balance_date', '<', $cutoff);
        if ($keepDates) {
            $candidates->whereNotIn('balance_date', $keepDates);
        }
        $toDelete = $candidates->count();

        if ($toDelete === 0) {
            $this->info('Nothing to prune.');
            return self::SUCCESS;
        }

        if ($dry) {
            $this->info("Dry run: would delete {$toDelete} row(s).");
            return self::SUCCESS;
        }

        // No ORDER BY: deletion order doesn't matter here, and adding one (e.g. by id)
        // forces MySQL to gather every matching row and sort it before applying LIMIT
        // (visible as "Using filesort" in EXPLAIN) instead of stopping at the first
        // N rows found walking the balance_date index range.
        $placeholders = $keepDates ? implode(',', array_fill(0, count($keepDates), '?')) : null;
        $sql = "DELETE FROM customer_balances WHERE balance_date < ?"
            . ($placeholders ? " AND balance_date NOT IN ({$placeholders})" : '')
            . " LIMIT ?";

        $deleted = 0;

        do {
            $bindings = array_merge([$cutoff], $keepDates, [$batch]);
            $affected = DB::delete($sql, $bindings);
            $deleted += $affected;

            if ($affected > 0) {
                $this->line("Deleted {$deleted}/{$toDelete}...");
            }
        } while ($affected > 0);

        $this->info("Pruning complete. Deleted {$deleted} row(s), kept month-end snapshots for " . count($keepDates) . ' earlier month(s).');

        return self::SUCCESS;
    }
}
