<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Models\Finance\MonthlyReportSnapshot;
use App\Services\Reports\MonthlyPerformanceReportService;
use Illuminate\Console\Command;
use Throwable;

class BuildMonthlyReportsCommand extends Command
{
    protected $signature = 'reports:build-monthly
        {month? : Month to build, YYYY-MM (defaults to the previous calendar month)}
        {--type=all : Which report to build: loans-deposits, branches or all}
        {--limit=100 : Top CIF gainers/losers per product stored for the Loans & Deposits Excel}
    ';

    protected $description = 'Build and store the monthly Loans & Deposits and/or Branch reports into monthly_report_snapshots (the email commands then send the stored copy).';

    public function handle(MonthlyPerformanceReportService $service): int
    {
        $month = trim((string) ($this->argument('month') ?? ''));
        if ($month === '') {
            $month = now()->timezone('Africa/Nairobi')->subMonthNoOverflow()->format('Y-m');
        }

        $type  = strtolower(trim((string) $this->option('type')));
        $types = match ($type) {
            'all'            => [MonthlyReportSnapshot::TYPE_LOANS_DEPOSITS, MonthlyReportSnapshot::TYPE_BRANCHES],
            'loans-deposits' => [MonthlyReportSnapshot::TYPE_LOANS_DEPOSITS],
            'branches'       => [MonthlyReportSnapshot::TYPE_BRANCHES],
            default          => null,
        };

        if ($types === null) {
            $this->error("Unknown --type={$type}. Use loans-deposits, branches or all.");
            return self::FAILURE;
        }

        $failed = false;

        foreach ($types as $t) {
            $this->info("Building {$t} report for {$month}...");

            try {
                $report = $t === MonthlyReportSnapshot::TYPE_BRANCHES
                    ? $service->buildBranches($month)
                    : $service->build($month, max(1, (int) $this->option('limit')));

                $service->save($t, $report);
                $this->info("  Stored {$t} snapshot for {$month}.");
            } catch (Throwable $e) {
                $failed = true;
                $this->error("  {$t} build failed: " . $e->getMessage());
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
