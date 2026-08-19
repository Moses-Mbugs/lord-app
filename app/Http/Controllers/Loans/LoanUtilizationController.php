<?php

namespace App\Http\Controllers\Loans;

use App\Exports\Loans\LoanUtilizationExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Loans\LoanUtilizationUploadRequest;
use App\Models\Loans\LoanUtilizationApprovedLimit;
use App\Models\Loans\LoanUtilizationSnapshot;
use App\Services\Loans\LoanUtilizationDashboardService;
use App\Services\Loans\LoanUtilizationImportService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoanUtilizationController extends Controller
{
    public function index(Request $request, LoanUtilizationDashboardService $dashboardService)
    {
        $snapshots = LoanUtilizationSnapshot::where('status', 'completed')
            ->orderByDesc('as_of_date')
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        $snapshot = null;

        if ($request->filled('snapshot')) {
            $snapshot = $snapshots->firstWhere('id', (int) $request->query('snapshot'));
        }

        $snapshot = $snapshot ?? $snapshots->first();

        $dashboard = $snapshot ? $dashboardService->build($snapshot) : null;

        $productNames = config('loan_utilization.product_names', []);

        return view('loans.loan-utilization.index', compact('snapshots', 'snapshot', 'dashboard', 'productNames'));
    }

    public function upload(LoanUtilizationUploadRequest $request, LoanUtilizationImportService $service)
    {
        try {
            $snapshot = $service->import(
                $request->file('loans_portfolio_file'),
                Auth::id()
            );

            return redirect()
                ->route('loans.loan-utilization.index', ['snapshot' => $snapshot->id])
                ->with('success', 'LOANS PORTFOLIO NEW report uploaded successfully. Rows processed: ' . number_format($snapshot->total_rows));
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    public function download($snapshotId, LoanUtilizationExport $export)
    {
        $snapshot = LoanUtilizationSnapshot::where('status', 'completed')->findOrFail($snapshotId);

        $fileName = 'Loan_Utilization_' . $snapshot->batch_reference . '.xlsx';

        $filePath = $export->generate($snapshot, $fileName);

        return response()
            ->download($filePath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    public function updateApprovedLimits(Request $request)
    {
        $productNames = config('loan_utilization.product_names', []);

        $limits = $request->input('approved_limit', []);

        foreach ($productNames as $name) {
            if (!array_key_exists($name, $limits)) {
                continue;
            }

            $value = (float) str_replace(',', '', $limits[$name]);

            LoanUtilizationApprovedLimit::updateOrCreate(
                ['product_name' => $name],
                ['approved_limit' => max(0, $value), 'updated_by' => Auth::id()]
            );
        }

        return redirect()
            ->back()
            ->with('success', 'Approved limits updated.');
    }
}
