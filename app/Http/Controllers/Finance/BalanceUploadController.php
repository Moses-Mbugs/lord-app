<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\BalanceImportService;
use App\Services\Reports\TopMoversService;
use Illuminate\Http\Request;

class BalanceUploadController extends Controller
{
    public function upload(Request $request, BalanceImportService $importer, TopMoversService $topMovers)
    {
        @set_time_limit(0);

        $data = $request->validate([
            'balances_file' => ['required', 'file'],
            'balance_date'  => ['nullable', 'date'],
            'build_movers'  => ['nullable', 'in:0,1'],
            'start_date'    => ['nullable', 'date'],
            'end_date'      => ['nullable', 'date'],
            'currency_type' => ['nullable', 'in:LCY,FCY'],
            'limit'         => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $file = $request->file('balances_file');
        if (!$file) {
            return back()->with('error', 'No file received.');
        }

        // IMPORTANT: allow large files (Laravel validation size uses KB if you add max)
        // You can optionally add validation: ['max:102400'] => 100MB in KB

        $result = $importer->importFromUpload(
            $file,
            $data['balance_date'] ?? null,
            2000
        );

        // Optional: build top movers immediately after upload
        if (($data['build_movers'] ?? '0') === '1') {
            $start = $data['start_date'] ?? $result['balance_date'];
            $end = $data['end_date'] ?? $result['balance_date'];
            $currencyType = $data['currency_type'] ?? 'LCY';
            $limit = (int)($data['limit'] ?? 20);

            $topMovers->build($start, $end, $currencyType, $limit);
        }

        return redirect()
            ->route('finance.home', [
                'start_date' => $data['start_date'] ?? $result['balance_date'],
                'end_date' => $data['end_date'] ?? $result['balance_date'],
                'currency_type' => $data['currency_type'] ?? 'LCY',
                'limit' => $data['limit'] ?? 20,
            ])
            ->with('success', "Imported {$result['inserted']} rows (skipped {$result['skipped']}) for {$result['balance_date']}.");
    }
}
