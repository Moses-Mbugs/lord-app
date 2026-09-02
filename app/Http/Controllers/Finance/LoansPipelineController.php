<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Reports\LoanDashboardService;
use App\Services\Reports\LoanImportService;
use App\Services\Reports\LoanMovementService;
use App\Mail\LoanMovementReportMail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class LoansPipelineController extends Controller
{
    public function index()
    {
        return view('finance.loans.pipeline', [
            'defaultDate' => now()->timezone('Africa/Nairobi')->toDateString(),
            'logLines'    => $this->readLastLogLines(200),
            'configTo'    => (array) config('reports.loans.to', []),
            'configCc'    => (array) config('reports.loans.cc', []),
        ]);
    }

    /**
     * Handle file upload: import the Excel, optionally send the report email.
     */
    public function upload(Request $request, LoanImportService $importer, LoanMovementService $movement, LoanDashboardService $dashboard)
    {
        @set_time_limit(0);

        $data = $request->validate([
            'loan_file'  => ['required', 'file', 'mimes:xlsx,xls'],
            'loan_date'  => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'send_email' => ['nullable', 'in:0,1'],
            'to'         => ['nullable', 'array'],
            'to.*'       => ['email'],
            'cc'         => ['nullable', 'array'],
            'cc.*'       => ['email'],
            'to_extra'   => ['nullable', 'string', 'max:500'],
            'cc_extra'   => ['nullable', 'string', 'max:500'],
        ]);

        $file = $request->file('loan_file');

        $result = $importer->importFromUpload($file, $data['loan_date'] ?? null);

        $asAtDate  = $result['as_at_date'];
        $startDate = $data['start_date'] ?? Carbon::parse($asAtDate)->subDay()->toDateString();

        if (($data['send_email'] ?? '0') === '1') {
            $combined = $movement->buildCombined($startDate, $asAtDate);

            $toList = $this->resolveRecipients($data['to'] ?? [], $data['to_extra'] ?? '', 'reports.loans.to');
            $ccList = $this->resolveRecipients($data['cc'] ?? [], $data['cc_extra'] ?? '', 'reports.loans.cc');

            Mail::to(array_filter($toList))
                ->cc(array_filter($ccList))
                ->send(new LoanMovementReportMail($combined, $startDate, $asAtDate));

            $this->appendLog("[LOAN] Email sent for {$startDate} → {$asAtDate}. Inserted {$result['inserted']} rows.");
        } else {
            $this->appendLog("[LOAN] Import complete for {$asAtDate}. Inserted {$result['inserted']} rows (skipped {$result['skipped']}).");
        }

        $this->refreshDashboard($dashboard, $asAtDate);

        return redirect()
            ->route('finance.loans.pipeline')
            ->with('success', "Imported {$result['inserted']} rows for {$asAtDate} (skipped {$result['skipped']}).")
            ->with('importedDate', $asAtDate);
    }

    /**
     * Send loan movement email without re-importing — just build from existing DB data.
     */
    public function send(Request $request, LoanMovementService $movement, LoanDashboardService $dashboard)
    {
        @set_time_limit(0);

        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date'],
            'to'         => ['nullable', 'array'],
            'to.*'       => ['email'],
            'cc'         => ['nullable', 'array'],
            'cc.*'       => ['email'],
            'to_extra'   => ['nullable', 'string', 'max:500'],
            'cc_extra'   => ['nullable', 'string', 'max:500'],
        ]);

        $start = Carbon::parse($data['start_date'])->toDateString();
        $end   = Carbon::parse($data['end_date'])->toDateString();

        $combined = $movement->buildCombined($start, $end);

        $toList = $this->resolveRecipients($data['to'] ?? [], $data['to_extra'] ?? '', 'reports.loans.to');
        $ccList = $this->resolveRecipients($data['cc'] ?? [], $data['cc_extra'] ?? '', 'reports.loans.cc');

        Mail::to(array_filter($toList))
            ->cc(array_filter($ccList))
            ->send(new LoanMovementReportMail($combined, $start, $end));

        $this->appendLog("[LOAN] Manual email sent for {$start} → {$end}.");

        $latestDate = $dashboard->latestDate();
        if ($latestDate) {
            $this->refreshDashboard($dashboard, $latestDate);
        }

        return redirect()
            ->route('finance.loans.pipeline')
            ->with('success', "Loan movement email sent for {$start} → {$end}.");
    }

    /**
     * Rebuilds the loan dashboard's cached payload so it reflects the latest
     * data immediately, rather than showing stale figures until the 15-minute
     * cache TTL in LoanDashboardService expires on its own. Best-effort: a
     * failure here shouldn't fail the import/send action that triggered it.
     */
    private function refreshDashboard(LoanDashboardService $dashboard, string $asOfDate): void
    {
        try {
            $dashboard->refreshDashboardPayload($asOfDate);
        } catch (\Throwable $e) {
            $this->appendLog("[LOAN] Dashboard refresh failed for {$asOfDate}: " . $e->getMessage());
        }
    }

    private function resolveRecipients(array $checked, string $extra, string $configKey): array
    {
        $list = array_filter($checked);

        if ($extra) {
            $extras = array_filter(array_map('trim', explode(',', $extra)));
            $list   = array_merge($list, $extras);
        }

        return $list ?: (array) config($configKey, []);
    }

    private function appendLog(string $message): void
    {
        file_put_contents(
            storage_path('logs/loans-pipeline.log'),
            sprintf("[%s] %s\n", now()->toDateTimeString(), $message),
            FILE_APPEND
        );
    }

    private function readLastLogLines(int $n = 200): array
    {
        $logPath = storage_path('logs/loans-pipeline.log');

        if (!file_exists($logPath)) {
            return [];
        }

        $lines = file($logPath, FILE_IGNORE_NEW_LINES);

        return $lines ? array_slice($lines, -$n) : [];
    }
}
