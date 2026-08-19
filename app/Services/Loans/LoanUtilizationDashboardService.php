<?php

namespace App\Services\Loans;

use App\Models\Loans\LoanUtilizationApprovedLimit;
use App\Models\Loans\LoanUtilizationSnapshot;
use Carbon\Carbon;

class LoanUtilizationDashboardService
{
    public function build(LoanUtilizationSnapshot $snapshot): array
    {
        $asOf = Carbon::parse($snapshot->as_of_date ?? $snapshot->created_at)->startOfDay();
        $ytdStart = $asOf->copy()->startOfYear();
        $mtdStart = $asOf->copy()->startOfMonth();
        $wtdStart = $asOf->copy()->startOfWeek(Carbon::MONDAY);

        $approvedLimits = LoanUtilizationApprovedLimit::query()
            ->pluck('approved_limit', 'product_name');

        $productNames = config('loan_utilization.product_names', []);

        $buckets = [];
        foreach ($productNames as $name) {
            $buckets[$name] = [
                'product_name' => $name,
                'approved_limit' => (float) ($approvedLimits[$name] ?? 0),
                'performing' => 0.0,
                'non_performing' => 0.0,
                'ytd' => 0.0,
                'mtd' => 0.0,
                'wtd' => 0.0,
                'last_day' => 0.0,
                'volume' => 0,
            ];
        }

        $snapshot->entries()
            ->select(['product_name', 'gross_outstanding_lcy', 'performance_status', 'value_date'])
            ->orderBy('id')
            ->chunk(1000, function ($entries) use (&$buckets, $ytdStart, $mtdStart, $wtdStart, $asOf) {
                foreach ($entries as $entry) {
                    $name = $entry->product_name ?? 'Unmapped - Review';
                    if (!isset($buckets[$name])) {
                        $buckets[$name] = [
                            'product_name' => $name,
                            'approved_limit' => 0.0,
                            'performing' => 0.0,
                            'non_performing' => 0.0,
                            'ytd' => 0.0,
                            'mtd' => 0.0,
                            'wtd' => 0.0,
                            'last_day' => 0.0,
                            'volume' => 0,
                        ];
                    }

                    $exposure = (float) $entry->gross_outstanding_lcy;
                    $buckets[$name]['volume']++;

                    if ($entry->performance_status === 'Performing') {
                        $buckets[$name]['performing'] += $exposure;
                    } else {
                        $buckets[$name]['non_performing'] += $exposure;
                    }

                    if ($entry->value_date) {
                        $valueDate = Carbon::parse($entry->value_date)->startOfDay();

                        if ($valueDate->between($ytdStart, $asOf)) {
                            $buckets[$name]['ytd'] += $exposure;
                        }
                        if ($valueDate->between($mtdStart, $asOf)) {
                            $buckets[$name]['mtd'] += $exposure;
                        }
                        if ($valueDate->between($wtdStart, $asOf)) {
                            $buckets[$name]['wtd'] += $exposure;
                        }
                        if ($valueDate->isSameDay($asOf)) {
                            $buckets[$name]['last_day'] += $exposure;
                        }
                    }
                }
            });

        $products = [];
        $grand = [
            'approved_limit' => 0.0, 'performing' => 0.0, 'non_performing' => 0.0,
            'ytd' => 0.0, 'mtd' => 0.0, 'wtd' => 0.0, 'last_day' => 0.0, 'volume' => 0,
        ];

        foreach ($buckets as $name => $b) {
            $total = $b['performing'] + $b['non_performing'];
            $npl = $total > 0 ? $b['non_performing'] / $total : 0.0;
            $util = $b['approved_limit'] > 0 ? $total / $b['approved_limit'] : null;

            $products[] = array_merge($b, [
                'total' => $total,
                'npl_ratio' => $npl,
                'utilisation' => $util,
                'rag_npl' => $this->ragNpl($npl),
                'rag_utilisation' => $util === null ? 'none' : $this->ragUtilisation($util),
            ]);

            foreach (['approved_limit', 'performing', 'non_performing', 'ytd', 'mtd', 'wtd', 'last_day', 'volume'] as $key) {
                $grand[$key] += $b[$key];
            }
        }

        // Keep the canonical product order first, then any ad-hoc buckets (e.g. legacy overrides).
        usort($products, function ($a, $b) use ($productNames) {
            $ia = array_search($a['product_name'], $productNames);
            $ib = array_search($b['product_name'], $productNames);
            return ($ia === false ? 999 : $ia) <=> ($ib === false ? 999 : $ib);
        });

        $grandTotal = $grand['performing'] + $grand['non_performing'];
        $grandNpl = $grandTotal > 0 ? $grand['non_performing'] / $grandTotal : 0.0;
        $grandUtil = $grand['approved_limit'] > 0 ? $grandTotal / $grand['approved_limit'] : null;

        return [
            'as_of_date' => $asOf->toDateString(),
            'products' => $products,
            'totals' => array_merge($grand, [
                'total' => $grandTotal,
                'npl_ratio' => $grandNpl,
                'utilisation' => $grandUtil,
                'rag_npl' => $this->ragNpl($grandNpl),
                'rag_utilisation' => $grandUtil === null ? 'none' : $this->ragUtilisation($grandUtil),
            ]),
        ];
    }

    protected function ragNpl(float $ratio): string
    {
        $t = config('loan_utilization.rag_thresholds.npl_ratio');

        if ($ratio <= $t['green']) return 'green';
        if ($ratio <= $t['amber']) return 'amber';
        return 'red';
    }

    protected function ragUtilisation(float $ratio): string
    {
        $t = config('loan_utilization.rag_thresholds.utilisation');

        if ($ratio <= $t['green']) return 'green';
        if ($ratio <= $t['amber']) return 'amber';
        return 'red';
    }
}
