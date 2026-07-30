<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\CustomerBalancesImportService;
use Illuminate\Http\Request;

class CustomerBalancesController extends Controller
{
    public function upload(Request $request, CustomerBalancesImportService $service)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:txt', 'max:51200'], // 50MB
            'file_date' => ['nullable', 'date_format:Y-m-d'],
            'batch' => ['nullable', 'integer', 'min:100', 'max:20000'],
        ]);

        $result = $service->importFromUpload(
            $request->file('file'),
            $data['file_date'] ?? null,
            (int)($data['batch'] ?? 2000)
        );

        return response()->json([
            'ok' => true,
            'message' => 'Balances imported successfully.',
            'data' => $result,
        ]);
    }
}
