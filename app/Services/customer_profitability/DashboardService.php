<?php

declare(strict_types=1);

namespace App\Services\customer_profitability;

use App\Models\customer_profitability\CustomerProfitabilityRecord as CPR;

class DashboardService
{
    /** Sorted "YYYY-MM" strings present in the batch's monthly records. */
    public function getMonths(int $batchId): array
    {
        return CPR::where('upload_batch_id', $batchId)
            ->monthly()
            ->whereNotNull('month')
            ->distinct()
            ->orderBy('month')
            ->pluck('month')
            ->toArray();
    }

    /** Top-level KPI summary from the YTD records. */
    public function getSummary(int $batchId): array
    {
        $agg = CPR::where('upload_batch_id', $batchId)
            ->ytd()
            ->selectRaw('
                COALESCE(SUM(total_revenue), 0)                              AS total_revenue,
                COUNT(*)                                                      AS customer_count,
                COALESCE(MAX(total_revenue), 0)                              AS top_customer_rev,
                SUM(CASE WHEN total_revenue < 0 THEN 1 ELSE 0 END)          AS loss_making_count,
                COALESCE(AVG(total_revenue), 0)                              AS avg_revenue
            ')
            ->first();

        $top = CPR::where('upload_batch_id', $batchId)
            ->ytd()
            ->orderByDesc('total_revenue')
            ->value('name');

        return [
            'total_revenue'     => (float) $agg->total_revenue,
            'customer_count'    => (int)   $agg->customer_count,
            'top_customer_rev'  => (float) $agg->top_customer_rev,
            'top_customer_name' => $top ?? '—',
            'loss_making_count' => (int)   $agg->loss_making_count,
            'avg_revenue'       => (float) $agg->avg_revenue,
        ];
    }

    /** Total YTD revenue keyed by segment: ['RC' => 1_200_000, ...] */
    public function getSegmentData(int $batchId): array
    {
        return CPR::where('upload_batch_id', $batchId)
            ->ytd()
            ->whereNotNull('segment')
            ->selectRaw('segment, SUM(total_revenue) AS revenue')
            ->groupBy('segment')
            ->orderByDesc('revenue')
            ->pluck('revenue', 'segment')
            ->map(fn($v) => (float) $v)
            ->toArray();
    }

    /** Monthly total revenue keyed by month: ['2025-07' => 980_000, ...] */
    public function getMonthlyTrend(int $batchId, array $months): array
    {
        $rows = CPR::where('upload_batch_id', $batchId)
            ->monthly()
            ->selectRaw('month, SUM(total_revenue) AS revenue')
            ->groupBy('month')
            ->pluck('revenue', 'month');

        $result = [];
        foreach ($months as $m) {
            $result[$m] = (float) ($rows[$m] ?? 0);
        }
        return $result;
    }

    /** Monthly revenue per segment: ['RC' => ['2025-07' => 400_000, ...], ...] */
    public function getMonthlyBySegment(int $batchId, array $months): array
    {
        $rows = CPR::where('upload_batch_id', $batchId)
            ->monthly()
            ->whereNotNull('segment')
            ->selectRaw('segment, month, SUM(total_revenue) AS revenue')
            ->groupBy('segment', 'month')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->segment][$row->month] = (float) $row->revenue;
        }

        foreach (array_keys($result) as $seg) {
            foreach ($months as $m) {
                $result[$seg][$m] ??= 0.0;
            }
        }

        return $result;
    }

    /** Top N customers by YTD revenue. */
    public function getTopCustomers(int $batchId, int $limit = 20): array
    {
        return CPR::where('upload_batch_id', $batchId)
            ->ytd()
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get(['name', 'segment', 'total_revenue'])
            ->map(fn($r) => [
                'name'    => $r->name,
                'segment' => $r->segment,
                'revenue' => (float) $r->total_revenue,
            ])
            ->toArray();
    }

    /** Customers with negative YTD revenue, ordered worst-first. */
    public function getLossMakers(int $batchId): array
    {
        return CPR::where('upload_batch_id', $batchId)
            ->ytd()
            ->where('total_revenue', '<', 0)
            ->orderBy('total_revenue')
            ->get(['name', 'segment', 'net_interest_income', 'total_revenue'])
            ->map(fn($r) => [
                'name'     => $r->name,
                'segment'  => $r->segment,
                'interest' => (float) $r->net_interest_income,
                'revenue'  => (float) $r->total_revenue,
            ])
            ->toArray();
    }

    /** Net interest / fees / FX split per segment (from YTD). */
    public function getRevenueMix(int $batchId): array
    {
        $rows = CPR::where('upload_batch_id', $batchId)
            ->ytd()
            ->whereNotNull('segment')
            ->selectRaw('
                segment,
                SUM(net_interest_income) AS interest,
                SUM(total_fees)          AS fees,
                SUM(fx_income)           AS fx
            ')
            ->groupBy('segment')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->segment] = [
                'interest' => (float) $row->interest,
                'fees'     => (float) $row->fees,
                'fx'       => (float) $row->fx,
            ];
        }
        return $result;
    }

    /** Total revenue and customer count per RM (from YTD), ordered by revenue desc. */
    public function getRMPerformance(int $batchId): array
    {
        return CPR::where('upload_batch_id', $batchId)
            ->ytd()
            ->whereNotNull('rm')
            ->where('rm', '!=', '')
            ->selectRaw('rm, SUM(total_revenue) AS total_revenue, COUNT(*) AS customer_count')
            ->groupBy('rm')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(fn($r) => [
                'rm'             => $r->rm,
                'total_revenue'  => (float) $r->total_revenue,
                'customer_count' => (int)   $r->customer_count,
            ])
            ->toArray();
    }

    /** Top-5 RMs monthly revenue trend: [['rm' => 'Alice', 'months' => ['2025-07' => ...]], ...] */
    public function getRMMonthly(int $batchId, array $months): array
    {
        $rows = CPR::where('upload_batch_id', $batchId)
            ->monthly()
            ->whereNotNull('rm')
            ->where('rm', '!=', '')
            ->selectRaw('rm, month, SUM(total_revenue) AS revenue')
            ->groupBy('rm', 'month')
            ->get();

        $byRm = [];
        foreach ($rows as $row) {
            $byRm[$row->rm][$row->month] = (float) $row->revenue;
        }

        $totals = [];
        foreach ($byRm as $rm => $monthData) {
            $totals[$rm] = array_sum($monthData);
        }
        arsort($totals);
        $topRms = array_slice(array_keys($totals), 0, 5);

        $result = [];
        foreach ($topRms as $rm) {
            $monthData = [];
            foreach ($months as $m) {
                $monthData[$m] = $byRm[$rm][$m] ?? 0.0;
            }
            $result[] = ['rm' => $rm, 'months' => $monthData];
        }
        return $result;
    }
}
