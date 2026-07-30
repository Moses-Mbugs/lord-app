<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Reports\CustomerTrendService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerTrendController extends Controller
{
    public function index(): View
    {
        return view('finance.customer-trend.index');
    }

    public function profile(Request $request, CustomerTrendService $service): JsonResponse
    {
        $data = $request->validate([
            'cif' => ['required', 'string', 'max:50'],
        ]);

        $profile = $service->profile($data['cif']);

        if (empty($profile['accounts']) && $profile['customer_name'] === null) {
            return response()->json([
                'found'   => false,
                'message' => 'No data found for CIF ' . $data['cif'],
            ], 404);
        }

        return response()->json([
            'found'   => true,
            'profile' => $profile,
        ]);
    }

    public function trend(Request $request, CustomerTrendService $service): JsonResponse
    {
        $data = $request->validate([
            'cif'  => ['required', 'string', 'max:50'],
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date'],
        ]);

        $trend = $service->trend($data['cif'], $data['from'] ?? null, $data['to'] ?? null);

        return response()->json([
            'cif'  => $data['cif'],
            'trend' => $trend,
        ]);
    }

    public function summary(Request $request, CustomerTrendService $service): JsonResponse
    {
        $data = $request->validate([
            'cif' => ['required', 'string', 'max:50'],
        ]);

        $summary = $service->summary($data['cif']);

        return response()->json([
            'cif'     => $data['cif'],
            'summary' => $summary,
        ]);
    }
}
