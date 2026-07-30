<?php

namespace App\Http\Controllers\Finance\customer_profitability;

use App\Http\Controllers\Controller;
use App\Models\customer_profitability\CustomerProfitabilityRecord;
use App\Models\customer_profitability\UploadBatch;
use App\Services\customer_profitability\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $service) {}

    public function searchCustomer(Request $request, int $id): JsonResponse
    {
        $batch = UploadBatch::findOrFail($id);
        $q     = trim($request->query('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $fields = [
            'cif', 'name', 'segment', 'rm',
            'interest_from_loans', 'interest_from_ods', 'interest_from_trade',
            'total_interest_income', 'interest_paid', 'net_ftp_interest',
            'net_interest_income', 'payments', 'receivables', 'liquidity',
            'cash_management', 'fees_and_commissions', 'trade_fees',
            'acquiring_expense', 'total_fees', 'fx_income', 'other_income',
            'total_revenue', 'ftp_income', 'ftp_expense',
        ];

        $ytdRecords = CustomerProfitabilityRecord::where('upload_batch_id', $batch->id)
            ->ytd()
            ->where(function ($q2) use ($q) {
                $q2->where('name', 'like', "%{$q}%")
                   ->orWhere('cif',  'like', "%{$q}%");
            })
            ->limit(10)
            ->get($fields)
            ->map(function ($r) use ($batch) {
                $row = $r->toArray();

                // Monthly trend for this customer in the same batch
                $row['monthly_trend'] = CustomerProfitabilityRecord::where('upload_batch_id', $batch->id)
                    ->monthly()
                    ->where('cif', $r->cif)
                    ->orderBy('month')
                    ->pluck('total_revenue', 'month')
                    ->map(fn($v) => (float) $v)
                    ->toArray();

                return $row;
            })
            ->values();

        return response()->json($ytdRecords);
    }

    public function index(int $id)
    {
        $batch   = UploadBatch::findOrFail($id);
        $batches = UploadBatch::latest()->get();
        $months  = $this->service->getMonths($batch->id);

        return view('finance.customer_profitability.dashboard.index', [
            'batch'            => $batch,
            'batches'          => $batches,
            'months'           => $months,
            'summary'          => $this->service->getSummary($batch->id),
            'segmentData'      => $this->service->getSegmentData($batch->id),
            'monthlyTrend'     => $this->service->getMonthlyTrend($batch->id, $months),
            'monthlyBySegment' => $this->service->getMonthlyBySegment($batch->id, $months),
            'topCustomers'     => $this->service->getTopCustomers($batch->id),
            'lossMakers'       => $this->service->getLossMakers($batch->id),
            'revenueMix'       => $this->service->getRevenueMix($batch->id),
            'rmPerformance'    => $this->service->getRMPerformance($batch->id),
            'rmMonthly'        => $this->service->getRMMonthly($batch->id, $months),
        ]);
    }
}
