<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Mail\BalancesImportStatusMail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use JsonMachine\Items;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use RuntimeException;
use Throwable;

/**
 * Pulls CORP_CUSTOMERS and INDI_CUSTOMERS from the intranet scripts API and upserts them
 * into customer_accounts_imports keyed on cust_ac_no: new accounts are inserted, existing
 * ones updated, and accounts missing from the API are left untouched (never deleted).
 *
 * The responses are large (50MB+), so each one is streamed straight to a temp file and then
 * read row by row with json-machine — the full payload is never held in memory. Both scripts
 * are downloaded before anything is written, so a failed download leaves the table as it was.
 */
class SyncCustomerAccountsCommand extends Command
{
    protected $signature = 'finance:sync-customer-accounts
        {--script=* : Only run these script(s), e.g. --script=CORP_CUSTOMERS (default: all configured)}
        {--keep-files : Keep the downloaded JSON files in storage/app/customer-accounts for inspection}
        {--no-rebuild : Skip rebuilding rm_workload_summary afterwards}';

    protected $description = 'Sync customer_accounts_imports from the intranet CORP/INDI customer scripts API (insert new, update existing).';

    private const TABLE = 'customer_accounts_imports';

    private const BATCH_SIZE = 1000;

    /** API field name => table column, for fields whose names differ. */
    private const RENAMED_FIELDS = [
        'account initiator -comp name' => 'account_initiator_name',
    ];

    private const COLUMNS = [
        'introducer', 'account_initiator_name', 'cust_category', 'eti_cif_class_category',
        'etibiseg2', 'acc_ofcr', 'officer_name', 'f12_cif', 'f12_ac_no', 'branch_code',
        'acy_withdrawable_bal', 'cust_ac_no', 'record_stat', 'account_class', 'ac_desc',
        'ac_open_date', 'dormancy_date', 'ac_stat_dormant', 'address_line1', 'lcy_curr_balance',
        'cheque_book_facility', 'atm_facility', 'telephone', 'e_mail',
    ];

    private const DATE_COLUMNS = ['ac_open_date', 'dormancy_date'];

    private const DECIMAL_COLUMNS = ['acy_withdrawable_bal', 'lcy_curr_balance'];

    /** @var array<string,true> API fields we don't have a column for, reported once at the end. */
    private array $unknownFields = [];

    public function handle(): int
    {
        $scripts = $this->option('script') ?: (array) config('reports.customer_accounts.scripts');
        $dir = storage_path('app/customer-accounts');
        File::ensureDirectoryExists($dir);

        $startedAt = now();

        // ── 1. Download every script first; bail out before touching the table if any fail ──
        $files = [];
        try {
            foreach ($scripts as $script) {
                $files[$script] = $this->download($script, $dir);
            }
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            $this->notify('error', 'Download failed', 'Could not fetch customer data from the intranet API. The table was not changed.', [
                'Error' => $e->getMessage(),
            ]);
            $this->cleanup($files);
            return self::FAILURE;
        }

        // ── 2. Upsert each file in batches ──────────────────────────────────────────────────
        $before = DB::table(self::TABLE)->count();
        $details = [];

        try {
            foreach ($files as $script => $path) {
                [$rows, $skipped] = $this->import($path);
                $details[$script] = number_format($rows) . ' rows' . ($skipped ? " ({$skipped} skipped: no cust_ac_no)" : '');
                $this->info("{$script}: upserted {$details[$script]}.");
            }
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            $this->notify('error', 'Import failed', 'The download worked but saving to customer_accounts_imports failed part-way. Rows already saved are kept; re-running is safe.', $details + [
                'Error' => $e->getMessage(),
            ]);
            $this->cleanup($files);
            return self::FAILURE;
        }

        $after = DB::table(self::TABLE)->count();
        $this->cleanup($files);

        $details['New accounts'] = number_format($after - $before);
        $details['Total in table'] = number_format($after);
        $details['Duration'] = $startedAt->diffForHumans(now(), true);
        if ($this->unknownFields) {
            $details['Ignored API fields'] = implode(', ', array_keys($this->unknownFields));
        }

        // ── 3. Downstream summary that is built from this table ────────────────────────────
        if (!$this->option('no-rebuild')) {
            $this->info('Rebuilding RM workload summary...');
            Artisan::call('finance:build-rm-workload', [], $this->output);
        }

        $this->notify('success', 'Customer accounts synced', 'customer_accounts_imports was updated from the intranet API.', $details);

        return self::SUCCESS;
    }

    /** Streams the API response for one script straight to disk and returns the file path. */
    private function download(string $script, string $dir): string
    {
        $path = $dir . DIRECTORY_SEPARATOR . $script . '.json';
        $this->info("Downloading {$script}...");

        $response = Http::timeout((int) config('reports.customer_accounts.timeout_seconds', 1800))
            ->connectTimeout(30)
            ->acceptJson()
            ->withOptions(['sink' => $path])
            ->post((string) config('reports.customer_accounts.api_url'), [
                'script_name' => $script,
                'parameters' => (object) [],
            ]);

        if (!$response->successful()) {
            $snippet = substr((string) @file_get_contents($path, false, null, 0, 300), 0, 300);
            throw new RuntimeException("{$script}: API returned HTTP {$response->status()}. {$snippet}");
        }

        $this->info(sprintf('  %s: %.1f MB', $script, filesize($path) / 1048576));

        return $path;
    }

    /** @return array{0:int,1:int} [rows upserted, rows skipped] */
    private function import(string $path): array
    {
        $items = Items::fromFile($path, ['pointer' => '/data', 'decoder' => new ExtJsonDecoder(true)]);

        $batch = [];
        $count = 0;
        $skipped = 0;
        $now = now();

        foreach ($items as $raw) {
            $row = $this->mapRow((array) $raw);

            if ($row['cust_ac_no'] === null) {
                $skipped++;
                continue;
            }

            // Keyed by account so a duplicate inside one batch just overwrites (last one wins).
            $batch[$row['cust_ac_no']] = $row + ['created_at' => $now, 'updated_at' => $now];
            $count++;

            if (count($batch) >= self::BATCH_SIZE) {
                $this->flush($batch);
                $batch = [];
            }
        }

        if ($batch) {
            $this->flush($batch);
        }

        return [$count, $skipped];
    }

    private function flush(array $batch): void
    {
        DB::table(self::TABLE)->upsert(
            array_values($batch),
            ['cust_ac_no'],
            [...array_diff(self::COLUMNS, ['cust_ac_no']), 'updated_at'],
        );
    }

    /** Turns one API record into a table row: renames fields, trims, converts dates and amounts. */
    private function mapRow(array $raw): array
    {
        $row = array_fill_keys(self::COLUMNS, null);

        foreach ($raw as $field => $value) {
            $column = self::RENAMED_FIELDS[$field] ?? $field;

            if (!array_key_exists($column, $row)) {
                $this->unknownFields[$field] = true;
                continue;
            }

            $value = is_string($value) ? trim($value) : $value;
            $row[$column] = ($value === '' ? null : $value);
        }

        foreach (self::DATE_COLUMNS as $column) {
            $row[$column] = $this->parseDate($row[$column]);
        }

        foreach (self::DECIMAL_COLUMNS as $column) {
            $row[$column] = $this->parseDecimal($row[$column]);
        }

        return $row;
    }

    /** Oracle-style "03-JUL-26" → "2026-07-03"; anything unparseable becomes null. */
    private function parseDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!d-M-y', (string) $value)->toDateString();
        } catch (Throwable) {
            try {
                return Carbon::parse((string) $value)->toDateString();
            } catch (Throwable) {
                return null;
            }
        }
    }

    private function parseDecimal(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = str_replace([',', ' '], '', (string) $value);

        return is_numeric($clean) ? $clean : null;
    }

    private function cleanup(array $files): void
    {
        if ($this->option('keep-files')) {
            return;
        }

        foreach ($files as $path) {
            @unlink($path);
        }
    }

    private function notify(string $level, string $heading, string $message, array $details = []): void
    {
        $to = array_values(array_filter(array_map('trim', explode(',', (string) config('reports.customer_accounts.notify_to', '')))));

        try {
            Mail::send(new BalancesImportStatusMail($level, $heading, $message, $details, $to ?: null, 'Customer Accounts Sync'));
        } catch (Throwable $e) {
            $this->error('Failed to send notification email: ' . $e->getMessage());
        }
    }
}
