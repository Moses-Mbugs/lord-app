<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

class BalancesPipelineController extends Controller
{
    /**
     * Show the manual trigger UI.
     */
    public function index()
    {
        $today = now()->timezone('Africa/Nairobi');

        return view('finance.balances.pipeline', [
            'defaultDate'       => $today->toDateString(),
            'defaultImportPath' => $this->buildImportPath($today->toDateString()),
            'logLines'          => $this->readLastLogLines(200),
        ]);
    }

    /**
     * Dispatch the pipeline in the background and redirect immediately.
     * Avoids 504 gateway timeouts on long-running imports.
     */
    public function run(Request $request)
    {
        $validated = $request->validate([
            'end_date'       => ['required', 'date_format:Y-m-d'],
            'start_date'     => ['nullable', 'date_format:Y-m-d'],
            'import_path'    => ['nullable', 'string', 'max:500'],
            'no_import'      => ['nullable', 'boolean'],
            'limit'          => ['nullable', 'integer', 'min:1', 'max:500'],
            'currency_limit' => ['nullable', 'integer', 'min:1', 'max:500'],
            'branch_limit'   => ['nullable', 'integer', 'min:1', 'max:500'],
            'to'             => ['nullable', 'string'],
            'cc'             => ['nullable', 'string'],
            'branch_to'      => ['nullable', 'string'],
            'branch_cc'      => ['nullable', 'string'],
        ]);

        $endDate    = $validated['end_date'];
        $startDate  = $validated['start_date'] ?? null;
        $importPath = trim($validated['import_path'] ?? '');
        $noImport   = (bool) ($validated['no_import'] ?? false);

        $args = ['end' => $endDate];

        $opts = [
            '--limit'          => (int) ($validated['limit'] ?? 20),
            '--currency-limit' => (int) ($validated['currency_limit'] ?? 10),
            '--branch-limit'   => (int) ($validated['branch_limit'] ?? 10),
        ];

        if ($startDate)       { $opts['--start']       = $startDate; }
        if ($importPath !== '') { $opts['--import-path'] = $importPath; }
        if ($noImport)        { $opts['--no-import']   = true; }

        foreach (['--to' => 'to', '--cc' => 'cc', '--branch-to' => 'branch_to', '--branch-cc' => 'branch_cc'] as $flag => $key) {
            if (!empty($validated[$key])) {
                $opts[$flag] = trim($validated[$key]);
            }
        }

        // Log that we're about to fire
        file_put_contents(
            storage_path('logs/balances-pipeline.log'),
            sprintf("\n[%s] [MANUAL RUN QUEUED] end=%s start=%s\n%s\n",
                now()->toDateTimeString(),
                $endDate,
                $startDate ?? 'auto',
                str_repeat('-', 80)
            ),
            FILE_APPEND
        );

        // Dispatch AFTER the HTTP response is sent — no timeout risk
        $command = array_merge($args, $opts);
        dispatch(function () use ($command) {
            Artisan::call('reports:run-balances', $command);
        })->afterResponse();

        return redirect()
            ->route('finance.balances.pipeline')
            ->with('dispatched', true)
            ->with('dispatchedFor', $endDate);
    }

    // -------------------------------------------------------------------------

    private function buildImportPath(string $date): string
    {
        $baseDir = rtrim((string) config('reports.balances.base_dir', '/mnt/eke_dailyflexcubereports'), '/');
        $country = trim((string) config('reports.balances.country_folder', 'Kenya'));
        $dt      = Carbon::parse($date);

        return "{$baseDir}/{$dt->format('Y')}/{$dt->format('M')}/{$dt->format('d')}/{$country}";
    }

    private function readLastLogLines(int $n = 200): array
    {
        $logPath = storage_path('logs/balances-pipeline.log');

        if (!file_exists($logPath)) {
            return [];
        }

        $lines = file($logPath, FILE_IGNORE_NEW_LINES);

        return $lines ? array_slice($lines, -$n) : [];
    }
}
