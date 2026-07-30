<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Keeps branch_daily_performance_summaries from growing forever: daily detail
 * on the branch dashboard is only useful for the trailing window analysts
 * actually compare against, so anything older than --days collapses down to
 * one snapshot per branch per month — the last balance_date built for that month.
 */
class PruneBranchDailyPerformanceSummariesCommand extends Command
{
    protected $signature = 'finance:prune-branch-daily-summary
        {--days=30 : Keep full daily detail for this many trailing calendar days}
        {--dry-run : Report what would be deleted without deleting anything}';

    protected $description = 'Deletes branch_daily_performance_summaries rows older than --days, keeping only each month\'s last built balance_date.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $dry  = (bool) $this->option('dry-run');

        $cutoff = now()->subDays($days)->startOfDay()->toDateString();

        $keepDates = DB::table('branch_daily_performance_summaries')
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

        $candidates = DB::table('branch_daily_performance_summaries')->where('balance_date', '<', $cutoff);
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

        $deleted = DB::table('branch_daily_performance_summaries')
            ->where('balance_date', '<', $cutoff)
            ->when($keepDates, fn ($q) => $q->whereNotIn('balance_date', $keepDates))
            ->delete();

        $this->info("Pruning complete. Deleted {$deleted} row(s), kept month-end snapshots for " . count($keepDates) . ' earlier month(s).');

        return self::SUCCESS;
    }
}
