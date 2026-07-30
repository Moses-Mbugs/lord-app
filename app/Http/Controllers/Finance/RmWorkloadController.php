<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Exports\Finance\RmAccountsExport;
use App\Http\Controllers\Controller;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RmWorkloadController extends Controller
{
    public function index(): View
    {
        return view('finance.rm-workload.index');
    }

    /**
     * Reads the precomputed rm_workload_summary table (rebuilt by
     * finance:build-rm-workload after each balances/loans import) instead of
     * aggregating customer_accounts_imports/customer_balances/loan_listings
     * live on every request.
     */
    public function data(Request $request): JsonResponse
    {
        $segment    = strtoupper(trim((string) $request->input('segment', '')));
        $subsegment = strtoupper(trim((string) $request->input('subsegment', '')));
        $search     = trim((string) $request->input('search', ''));

        $query = DB::table('rm_workload_summary');

        if ($segment !== '') {
            $query->whereRaw('UPPER(TRIM(segment)) = ?', [$segment]);
        }

        if ($subsegment !== '') {
            $query->whereRaw('UPPER(TRIM(subsegment)) = ?', [$subsegment]);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('rm_code', 'like', "%{$search}%")
                    ->orWhere('officer_name', 'like', "%{$search}%");
            });
        }

        $rows = $query->orderByDesc('total_deposits')->get();

        // Fetched unfiltered — every row shares the same snapshot dates (the
        // whole table is rebuilt in one pass), but the filtered $rows above
        // may be empty and we still want the dates for the page header.
        $dates = DB::table('rm_workload_summary')
            ->selectRaw('MAX(balance_date) as balance_date, MAX(loan_date) as loan_date')
            ->first();

        return response()->json([
            'rows'         => $rows,
            'balance_date' => $dates->balance_date ?? null,
            'loan_date'    => $dates->loan_date ?? null,
        ]);
    }

    /**
     * Every account (from customer_accounts_imports) assigned to the given RM.
     */
    public function accounts(Request $request): JsonResponse
    {
        $rmCode = strtoupper(trim((string) $request->input('rm_code', '')));

        if ($rmCode === '') {
            return response()->json(['success' => false, 'message' => 'rm_code is required.'], 422);
        }

        $rows = $this->rmAccountsQuery($rmCode)->get();

        return response()->json([
            'success' => true,
            'rm_code' => $rmCode,
            'rows'    => $rows,
        ]);
    }

    /**
     * Excel download of the same account list shown in the RM Workload drawer.
     */
    public function exportAccounts(Request $request): BinaryFileResponse
    {
        $rmCode = strtoupper(trim((string) $request->input('rm_code', '')));

        abort_if($rmCode === '', 422, 'rm_code is required.');

        $rows = $this->rmAccountsQuery($rmCode)->get();

        $filename = 'rm-accounts-' . $rmCode . '-' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new RmAccountsExport($rows, $rmCode), $filename);
    }

    private function rmAccountsQuery(string $rmCode): Builder
    {
        // Join is constrained to a single (currency_type, balance_date) pair so it
        // can only ever match one customer_balances row per account — otherwise
        // every historical snapshot for that account would multiply the row.
        $balanceDate = DB::table('customer_balances')->max('balance_date');

        return DB::table('customer_accounts_imports as cai')
            ->leftJoin('customer_balances as cb', function ($join) use ($balanceDate) {
                $join->on('cb.cust_ac_no', '=', 'cai.cust_ac_no')
                    ->where('cb.currency_type', '=', 'LCY');

                if ($balanceDate) {
                    $join->where('cb.balance_date', '=', $balanceDate);
                }
            })
            ->selectRaw("
                cai.cust_ac_no                            AS account_number,
                cai.f12_cif                                AS cif,
                COALESCE(cb.customer_name, cai.ac_desc)    AS customer_name,
                cai.branch_code                            AS branch_code,
                cai.ac_stat_dormant                        AS dormant_flag
            ")
            ->whereRaw('UPPER(TRIM(cai.acc_ofcr)) = ?', [$rmCode])
            ->orderBy('cai.cust_ac_no');
    }
}
