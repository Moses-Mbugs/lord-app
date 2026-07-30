<?php


// out of order using RunBalancesTopMoversCommand instead of ForDate, because the latter is more flexible and can be called by the former, but not vice versa.

// declare(strict_types=1);

// namespace App\Console\Commands\Finance;

// use Carbon\Carbon;
// use Illuminate\Console\Command;
// use Illuminate\Support\Facades\Artisan;
// use Throwable;

// class RunBalancesTopMoversForDate extends Command
// {
//     protected $signature = 'reports:run-balances
//         {end? : End date YYYY-MM-DD (defaults to yesterday)}
//         {--start= : Optional start date YYYY-MM-DD (defaults to end-1 day)}
//         {--limit= : Optional limit per bucket (defaults to config)}
//         {--no-import : Skip import:balances step}
//         {--to= : Override TO (comma-separated)}
//         {--cc= : Override CC (comma-separated)}
//     ';

//     protected $description = 'Import balances for a date folder -> build LCY/FCY top movers -> email report (no .env).';

//     public function handle(): int
//     {
//         try {
//             $endArg = (string) ($this->argument('end') ?? '');
//             $endDate = $endArg !== ''
//                 ? Carbon::parse($endArg)->toDateString()
//                 : now()->subDay()->toDateString();

//             $startOpt = (string) ($this->option('start') ?? '');
//             $startDate = $startOpt !== ''
//                 ? Carbon::parse($startOpt)->toDateString()
//                 : Carbon::parse($endDate)->subDay()->toDateString();

//             $limit = (int) ($this->option('limit') ?: config('reports.balances.limit', 20));
//             $limit = max(1, $limit);

//             $this->info("Balances Top Movers: {$startDate} → {$endDate}");
//             $this->line("Limit: {$limit}");

//             $shouldImport = !$this->option('no-import');

//             // Build folder path like /mnt/eke_dailyflexcubereports/2026/Feb/13/Kenya
//             $importPath = $this->balancesPathForDate($endDate);

//             // 1) Import balances (optional)
//             if ($shouldImport) {
//                 if (!is_dir($importPath)) {
//                     $this->error("Balances folder not found: {$importPath}");
//                     return self::FAILURE;
//                 }

//                 $this->info("1/3 Importing balances from: {$importPath}");
//                 Artisan::call('import:balances', ['path' => $importPath], $this->output);
//             } else {
//                 $this->warn("1/3 Skipping import (--no-import).");
//             }

//             // 2) Build top movers
//             $this->info("2/3 Building top movers LCY");
//             Artisan::call('reports:build-top-movers', [
//                 'start' => $startDate,
//                 'end' => $endDate,
//                 'currencyType' => 'LCY',
//                 '--limit' => $limit,
//             ], $this->output);

//             $this->info("2/3 Building top movers FCY");
//             Artisan::call('reports:build-top-movers', [
//                 'start' => $startDate,
//                 'end' => $endDate,
//                 'currencyType' => 'FCY',
//                 '--limit' => $limit,
//             ], $this->output);

//             // 3) Email top movers (defaults from config unless overridden)
//             $to = (string) ($this->option('to') ?: implode(',', (array) config('reports.balances.top_movers_to', [])));
//             $cc = (string) ($this->option('cc') ?: implode(',', (array) config('reports.balances.top_movers_cc', [])));

//             if (trim($to) === '') {
//                 $this->error("No recipients configured. Set reports.balances.top_movers_to in config/reports.php or pass --to=");
//                 return self::FAILURE;
//             }

//             $this->info("3/3 Emailing top movers to: {$to}");
//             Artisan::call('reports:email-top-movers', [
//                 'start' => $startDate,
//                 'end' => $endDate,
//                 '--to' => $to,
//                 '--cc' => $cc,
//                 '--limit' => $limit,
//             ], $this->output);

//             $this->info("Done. Email sent for {$startDate} → {$endDate}");
//             return self::SUCCESS;

//         } catch (Throwable $e) {
//             $this->error("Run failed: " . $e->getMessage());
//             return self::FAILURE;
//         }
//     }

//     private function balancesPathForDate(string $date): string
//     {
//         $base = rtrim((string) config('reports.balances.base_dir'), '/');
//         $country = trim((string) config('reports.balances.country_folder', 'Kenya'), '/');

//         $dt = Carbon::parse($date);

//         // Example requires: 2026/Feb/13/Kenya
//         $year = $dt->format('Y');  // 2026
//         $mon  = $dt->format('M');  // Feb (Jan, Feb, Mar, Apr...)
//         $day  = $dt->format('j');  // 13 (no leading zero)

//         return "{$base}/{$year}/{$mon}/{$day}/{$country}";
//     }
// }
