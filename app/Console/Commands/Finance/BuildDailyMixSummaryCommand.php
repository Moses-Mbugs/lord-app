<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Services\Reports\FinanceDailyMixSummaryService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class BuildDailyMixSummaryCommand extends Command
{
    protected $signature = 'finance:build-daily-mix
        {date? : Balance date YYYY-MM-DD. Defaults to latest customer_balances date}
        {--fresh : Force rebuild even if summaries already exist for the date}';

    protected $description = 'Build and store daily finance mix summaries (overall + segment currency/deposit mix) from customer_balances.';

    public function __construct(private readonly FinanceDailyMixSummaryService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $dateArg = trim((string) ($this->argument('date') ?? ''));
            $date = $dateArg !== ''
                ? Carbon::parse($dateArg)->toDateString()
                : $this->service->latestBalanceDate();

            if (! $date) {
                $this->error('No balance_date found in customer_balances.');
                return self::FAILURE;
            }

            $this->info("Building daily mix summaries for {$date}...");

            $result = $this->service->buildForDate(
                $date,
                (bool) $this->option('fresh')
            );

            $overall = $result['overall'] ?? [];
            $segments = $result['segments'] ?? [];

            $this->line('');
            $this->info('Overall Currency Mix');
            $this->line(sprintf('  LCY: %s (%.2f%%)', $this->money((float) ($overall['lcy_amount'] ?? 0)), (float) ($overall['lcy_pct'] ?? 0)));
            $this->line(sprintf('  FCY: %s (%.2f%%)', $this->money((float) ($overall['fcy_amount'] ?? 0)), (float) ($overall['fcy_pct'] ?? 0)));

            $this->line('');
            $this->info('Overall Deposit Mix');
            $this->line(sprintf('  Current: %s (%.2f%%)', $this->money((float) ($overall['current_amount'] ?? 0)), (float) ($overall['current_pct'] ?? 0)));
            $this->line(sprintf('  Savings: %s (%.2f%%)', $this->money((float) ($overall['savings_amount'] ?? 0)), (float) ($overall['savings_pct'] ?? 0)));
            $this->line(sprintf('  Term   : %s (%.2f%%)', $this->money((float) ($overall['term_amount'] ?? 0)), (float) ($overall['term_pct'] ?? 0)));

            if (!empty($segments)) {
                $this->line('');
                $this->info('Segment Currency Mix');
                foreach ($segments as $segmentCode => $summary) {
                    $name = (string) ($summary['segment_name'] ?? $segmentCode);
                    $this->line(sprintf(
                        '  [%s] %s -> LCY %.2f%% / FCY %.2f%% (%s rows)',
                        $segmentCode,
                        $name,
                        (float) ($summary['lcy_pct'] ?? 0),
                        (float) ($summary['fcy_pct'] ?? 0),
                        number_format((int) ($summary['source_row_count'] ?? 0))
                    ));
                }
            }

            $this->line('');
            $this->info('Done.');
            $this->line('  Overall source rows: ' . number_format((int) ($overall['source_row_count'] ?? 0)));
            $this->line('  Overall total positive LCY balance: ' . $this->money((float) ($overall['total_positive_lcy_balance'] ?? 0)));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Build failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function money(float $value): string
    {
        $abs = abs($value);
        $prefix = $value < 0 ? '-KES ' : 'KES ';

        if ($abs >= 1000000000) {
            return $prefix . number_format($abs / 1000000000, 2) . 'B';
        }

        if ($abs >= 1000000) {
            return $prefix . number_format($abs / 1000000, 2) . 'M';
        }

        if ($abs >= 1000) {
            return $prefix . number_format($abs / 1000, 2) . 'K';
        }

        return $prefix . number_format($abs, 2);
    }
}
