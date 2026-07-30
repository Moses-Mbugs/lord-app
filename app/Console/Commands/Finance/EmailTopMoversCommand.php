<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Mail\TopMoversReportMail;
use App\Services\Reports\TopMoversService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class EmailTopMoversCommand extends Command
{
    protected $signature = 'reports:email-top-movers
        {start : Start date YYYY-MM-DD (requested start)}
        {end : End date YYYY-MM-DD}
        {--to= : Override TO recipients. Accepts comma/semicolon/space separated emails}
        {--cc= : Override CC recipients. Accepts comma/semicolon/space separated emails}
        {--limit=20 : CIF/Corporate movers per bucket (gainers and losers each)}
        {--currency-limit=10 : LCY/FCY movers per bucket}
    ';

    protected $description = 'Email Top Movers report (reads from top_movers table) + Segment Movers summary (reads from segment_movers).';

    public function handle(TopMoversService $topMoversService): int
    {
        $requestedStart  = (string) $this->argument('start');
        $end             = (string) $this->argument('end');
        $limit           = max(1, (int) $this->option('limit'));           // CIF + Corporate
        $currencyLimit   = max(1, (int) $this->option('currency-limit'));  // LCY / FCY

        // --- TO/CC recipients (arrays, never comma-string) ---
        $toOpt = (string) ($this->option('to') ?? '');
        $to = $toOpt !== ''
            ? $this->parseEmails($toOpt)
            : $this->parseEmails(config('reports.balances.top_movers_to', []));

        if (empty($to)) {
            $this->error('No TO recipients configured. Set reports.balances.top_movers_to or pass --to=');
            return self::FAILURE;
        }

        $ccOpt = (string) ($this->option('cc') ?? '');
        $cc = $ccOpt !== ''
            ? $this->parseEmails($ccOpt)
            : $this->parseEmails(config('reports.balances.top_movers_cc', []));

        // Validate emails early
        $invalid = array_values(array_filter(array_merge($to, $cc), fn ($e) => !filter_var($e, FILTER_VALIDATE_EMAIL)));
        if (!empty($invalid)) {
            $this->error('Invalid email(s): ' . implode(', ', $invalid));
            return self::FAILURE;
        }

        // ---- Fetch top_movers rows for requested period ----
        $rows = $this->fetchTopMoversRows($requestedStart, $end);

        // If empty, fallback to effective start_date used in DB (weekends/holidays)
        $effectiveStart = $requestedStart;

        if ($rows->isEmpty()) {
            $fallbackStart = DB::table('top_movers')
                ->whereDate('end_date', $end)
                ->max('start_date');

            if ($fallbackStart) {
                $effectiveStart = (string) $fallbackStart;
                $rows = $this->fetchTopMoversRows($effectiveStart, $end);
            }
        }

        if ($rows->isEmpty()) {
            $this->warn("No rows found in top_movers for requested {$requestedStart} → {$end} (and no fallback found).");
            $this->warn("Did you run reports:build-top-movers for that end date?");
            return self::FAILURE;
        }

        if ($effectiveStart !== $requestedStart) {
            $this->line("⚠ Weekend/holiday fallback applied: using {$effectiveStart} → {$end} (requested was {$requestedStart} → {$end})");
        }

        // ---- Group CIF_ONLY + CIF_CURRENCY movers and segment overview (shared with the dashboard page) ----
        $grouped  = $topMoversService->fetchGroupedMovers($effectiveStart, $end, $limit, $currencyLimit);
        $segments = $topMoversService->fetchSegmentOverview($effectiveStart, $end);

        Mail::to($to)
            ->cc($cc)
            ->send(new TopMoversReportMail(
                $effectiveStart,
                $end,
                $grouped,
                $limit,
                $segments
            ));

        $this->info('Top movers email sent.');
        $this->line('TO: ' . implode(', ', $to));
        $this->line('CC: ' . (empty($cc) ? '(none)' : implode(', ', $cc)));
        $this->line("Period: {$effectiveStart} → {$end} | CIF limit: {$limit} | LCY/FCY limit: {$currencyLimit}");

        // Warn if CIF buckets came up short — rebuild with a higher --limit
        $short = [];
        foreach (['GAIN', 'LOSS'] as $dir) {
            $count = $grouped['CIF_ONLY'][$dir]->count();
            if ($count < $limit) {
                $short[] = "CIF_ONLY/{$dir}={$count}";
            }
        }
        if (!empty($short)) {
            $this->warn("Some CIF buckets have fewer than {$limit} rows: " . implode(', ', $short));
            $this->warn("Consider rebuilding: php artisan reports:build-top-movers {$effectiveStart} {$end} LCY --scope=cif_only --limit=60");
        }

        return self::SUCCESS;
    }

    private function fetchTopMoversRows(string $start, string $end): Collection
    {
        return DB::table('top_movers')
            ->whereDate('start_date', $start)
            ->whereDate('end_date', $end)
            ->orderBy('start_date')
            ->orderBy('end_date')
            ->orderBy('currency_type')
            ->orderByDesc('movement')
            ->get();
    }

    /**
     * Accepts:
     *  - array of emails from config
     *  - string of emails separated by comma/semicolon/space/newline
     */
    private function parseEmails(array|string|null $input): array
    {
        if (is_array($input)) {
            $emails = $input;
        } else {
            $raw = trim((string) ($input ?? ''));
            if ($raw === '') return [];
            $emails = preg_split('/[,\s;]+/', $raw) ?: [];
        }

        $emails = array_map(fn ($e) => trim((string) $e), $emails);
        $emails = array_values(array_filter($emails, fn ($e) => $e !== ''));
        $emails = array_map('strtolower', $emails);

        return array_values(array_unique($emails));
    }
}
