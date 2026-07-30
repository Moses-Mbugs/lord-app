<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunBalancesTopMoversCommand extends Command
{
    /** @var array<string, int> Wall-clock ms per pipeline step, keyed by its label. */
    private array $timings = [];

    protected $signature = 'reports:run-balances
        {end? : End date YYYY-MM-DD (optional if --auto)}
        {--auto : Auto-resolve end date and import path}
        {--date= : Explicit end date for --auto mode (YYYY-MM-DD)}
        {--start= : Optional start date YYYY-MM-DD (if omitted, resolves to latest available balance_date < end)}
        {--import-path= : Optional folder path to balances files (auto computed in --auto mode if omitted)}
        {--no-import : Skip import step}
        {--limit=20 : CIF-only top movers limit (gainers + losers)}
        {--currency-limit=10 : CIF+Currency top movers limit per bucket (LCY/FCY gain/loss)}
        {--branch-limit=10 : Branch movers limit (top gainers + losers)}
        {--to= : Override TO recipients (comma/semicolon/space separated)}
        {--cc= : Override CC recipients (comma/semicolon/space separated)}
        {--branch-to= : Override Branch Movers TO recipients}
        {--branch-cc= : Override Branch Movers CC recipients}
    ';

    protected $description = 'Run balances pipeline (import) -> build top movers -> build segment movers -> build sub-segment movers -> build branch movers -> build rm movers -> build daily mix summary -> build branch daily summary -> email reports (weekend/holiday safe).';

    public function handle(): int
    {
        try {
            $auto = (bool) $this->option('auto');

            if ($auto) {
                $dateOpt = trim((string) ($this->option('date') ?? ''));
                $endDate = $dateOpt !== ''
                    ? Carbon::parse($dateOpt)->toDateString()
                    : now()->timezone('Africa/Nairobi')->toDateString();
            } else {
                $endArg = (string) ($this->argument('end') ?? '');
                if (trim($endArg) === '') {
                    $this->error('Missing end date. Provide {end} or use --auto.');
                    return self::FAILURE;
                }
                $endDate = Carbon::parse($endArg)->toDateString();
            }

            $limit         = max(1, (int) $this->option('limit'));
            $currencyLimit = max(1, (int) $this->option('currency-limit'));
            $branchLimit   = max(1, (int) $this->option('branch-limit'));

            $noImport      = (bool) $this->option('no-import');
            $importPathOpt = trim((string) ($this->option('import-path') ?? ''));
            $importPath    = $importPathOpt;

            if ($auto && $importPath === '') {
                $importPath = $this->buildImportPath($endDate);
            }

            $toOpt = trim((string) ($this->option('to') ?? ''));
            $ccOpt = trim((string) ($this->option('cc') ?? ''));

            $toList = $toOpt !== ''
                ? $this->parseEmails($toOpt)
                : $this->parseEmails(config('reports.balances.top_movers_to', []));

            $ccList = $ccOpt !== ''
                ? $this->parseEmails($ccOpt)
                : $this->parseEmails(config('reports.balances.top_movers_cc', []));

            if (empty($toList)) {
                $this->error('No recipient set. Configure reports.balances.top_movers_to or pass --to=.');
                return self::FAILURE;
            }

            $branchToOpt = trim((string) ($this->option('branch-to') ?? ''));
            $branchCcOpt = trim((string) ($this->option('branch-cc') ?? ''));

            $branchToList = $branchToOpt !== ''
                ? $this->parseEmails($branchToOpt)
                : $this->parseEmails(config('reports.balances.branch_movers_to', $toList));

            $branchCcList = $branchCcOpt !== ''
                ? $this->parseEmails($branchCcOpt)
                : $this->parseEmails(config('reports.balances.branch_movers_cc', $ccList));

            $startOpt = trim((string) ($this->option('start') ?? ''));
            if ($startOpt !== '') {
                $startDate = Carbon::parse($startOpt)->toDateString();
            } else {
                $startDate = $this->resolveLatestBalanceDateBefore($endDate);

                if (! $startDate) {
                    $this->error("No previous balance_date found before {$endDate}. Import balances first.");
                    return self::FAILURE;
                }
            }

            $this->info("Balances Pipeline: {$startDate} → {$endDate}");
            $this->line("CIF ONLY limit     : {$limit}");
            $this->line("CIF+Currency limit : {$currencyLimit}");
            $this->line("Branch movers limit: {$branchLimit}");

            if ($noImport) {
                $this->warn('1/11 Skipping import (--no-import).');
            } else {
                if ($importPath === '') {
                    $this->warn('1/11 Skipping import (no --import-path provided).');
                } else {
                    if (! is_dir($importPath)) {
                        $this->error("Import path not found or not a directory: {$importPath}");
                        return self::FAILURE;
                    }

                    $this->runStep("1/11 Importing balances from: {$importPath}", 'import:balances', [
                        'path' => $importPath,
                    ]);
                }
            }

            $endExists = DB::table('customer_balances')
                ->where('balance_date', $endDate)
                ->exists();

            if (! $endExists) {
                $this->error("No balances found for end date {$endDate}. Ensure the correct file/folder exists and import succeeded.");
                return self::FAILURE;
            }

            $this->runStep('2/11 Building top movers (CIF ONLY - LCY equivalent)', 'reports:build-top-movers', [
                'start'        => $startDate,
                'end'          => $endDate,
                'currencyType' => 'LCY',
                '--limit'      => $limit,
                '--scope'      => 'cif_only',
            ]);

            $this->runStep('3/11 Building segment movers (CB/CM/CS - LCY equivalent)', 'reports:build-segment-movers', [
                'start' => $startDate,
                'end'   => $endDate,
            ]);

            $this->runStep('4/11 Building sub-segment movers (CIF-only LCY equivalent)', 'reports:build-sub-segment-movers', [
                'start' => $startDate,
                'end'   => $endDate,
            ]);

            $this->runStep('5/11 Building branch movers (P50 excluded)', 'reports:build-branch-movers', [
                'start'   => $startDate,
                'end'     => $endDate,
                '--limit' => $branchLimit,
            ]);

            $this->runStep('6/12 Building RM movers', 'reports:build-rm-movers', [
                'start' => $startDate,
                'end'   => $endDate,
            ]);

            $this->runStep('7/12 Building daily finance mix summary', 'finance:build-daily-mix', [
                'date' => $endDate,
                '--fresh' => true,
            ]);

            $this->runStep('8/12 Building branch daily performance summary', 'finance:build-branch-daily-summary', [
                'date' => $endDate,
                '--fresh' => true,
            ]);

            $this->runStep('Pruning old branch daily performance summary rows...', 'finance:prune-branch-daily-summary');

            $this->runStep('9/12 Building top movers (CIF + Currency) LCY', 'reports:build-top-movers', [
                'start'        => $startDate,
                'end'          => $endDate,
                'currencyType' => 'LCY',
                '--limit'      => $currencyLimit,
                '--scope'      => 'cif_currency',
            ]);

            $this->runStep('10/12 Building top movers (CIF + Currency) FCY', 'reports:build-top-movers', [
                'start'        => $startDate,
                'end'          => $endDate,
                'currencyType' => 'FCY',
                '--limit'      => $currencyLimit,
                '--scope'      => 'cif_currency',
            ]);

            $toStr = implode(',', $toList);
            $ccStr = implode(',', $ccList);

            $this->runStep("11/12 Emailing top movers to: {$toStr}", 'reports:email-top-movers', [
                'start'   => $startDate,
                'end'     => $endDate,
                '--to'    => $toStr,
                '--cc'    => $ccStr,
                '--limit' => $limit,
            ]);

            $bToStr = implode(',', $branchToList);
            $bCcStr = implode(',', $branchCcList);

            $this->runStep("12/12 Emailing branch movers to: {$bToStr}", 'reports:email-branch-movers', [
                'start'   => $startDate,
                'end'     => $endDate,
                '--to'    => $bToStr,
                '--cc'    => $bCcStr,
                '--limit' => $branchLimit,
            ]);

            $this->info("Done. Pipeline complete for {$startDate} → {$endDate}");

            $this->line('');
            $this->info('Step timings:');
            foreach ($this->timings as $label => $ms) {
                $this->line(sprintf('  %-55s %10s ms', $label, number_format($ms)));
            }

            Log::info('balances pipeline step timings', $this->timings);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Run failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Runs an Artisan command as one pipeline step, printing its label first (so
     * output stays interleaved in order) and recording its wall-clock time into
     * $this->timings for the end-of-run summary and log entry.
     */
    private function runStep(string $label, string $command, array $params = []): void
    {
        $this->info($label);

        $start = microtime(true);
        Artisan::call($command, $params, $this->output);
        $this->timings[$label] = (int) round((microtime(true) - $start) * 1000);
    }

    private function buildImportPath(string $endDate): string
    {
        $baseDir = rtrim((string) config('reports.balances.base_dir', '/mnt/eke_dailyflexcubereports'), '/');
        $country = trim((string) config('reports.balances.country_folder', 'Kenya'));

        $dt  = Carbon::parse($endDate);
        $year = $dt->format('Y');
        $mon  = $dt->format('M');
        $day  = $dt->format('d');

        return "{$baseDir}/{$year}/{$mon}/{$day}/{$country}";
    }

    private function resolveLatestBalanceDateBefore(string $endDate): ?string
    {
        $d = DB::table('customer_balances')
            ->where('balance_date', '<', $endDate)
            ->max('balance_date');

        return $d ? Carbon::parse((string) $d)->toDateString() : null;
    }

    private function parseEmails(array|string|null $input): array
    {
        if (is_array($input)) {
            $emails = $input;
        } else {
            $raw = trim((string) ($input ?? ''));
            if ($raw === '') {
                return [];
            }
            $emails = preg_split('/[,\s;]+/', $raw) ?: [];
        }

        $emails = array_map(fn($e) => strtolower(trim((string) $e)), $emails);
        $emails = array_values(array_filter($emails, fn($e) => $e !== ''));
        $emails = array_values(array_filter($emails, fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));

        return array_values(array_unique($emails));
    }
}
