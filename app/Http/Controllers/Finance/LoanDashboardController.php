<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Reports\LoanDashboardService;

class LoanDashboardController extends Controller
{
    public function __construct(private LoanDashboardService $service)
    {
    }

    public function index()
    {
        $asOfDate = $this->service->latestDate();

        if (!$asOfDate) {
            return view('finance.loans.dashboard', $this->service->emptyPayload());
        }

        return view('finance.loans.dashboard', $this->service->buildDashboardPayload($asOfDate));
    }
}
