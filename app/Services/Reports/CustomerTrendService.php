<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerTrendService
{
    public function profile(string $cif): array
    {
        $cif = trim($cif);

        $accounts = DB::table('customer_accounts_imports as cai')
            ->where('cai.f12_cif', $cif)
            ->leftJoin('sub_segment_mappings as sm', 'sm.mis_code', '=', DB::raw('UPPER(TRIM(cai.etibiseg2))'))
            ->select([
                'cai.f12_ac_no',
                'cai.cust_ac_no',
                'cai.ac_desc',
                'cai.account_class',
                'cai.branch_code',
                'cai.acc_ofcr',
                'cai.etibiseg2',
                'cai.ac_open_date',
                'cai.record_stat',
                'sm.business_segment_name',
                'sm.code_desc',
                'sm.business',
            ])
            ->get();

        $first = $accounts->first();

        $customerName = $this->resolveCustomerName($cif);

        $latestBalance = DB::table('customer_balances')
            ->where('cif', $cif)
            ->orderByDesc('balance_date')
            ->value('customer_name');

        $name = $customerName ?: ($latestBalance ? trim((string) $latestBalance) : null);

        $loans = $this->loans($cif);

        return [
            'cif'                  => $cif,
            'customer_name'        => $name,
            'rm_code'              => $first ? trim((string) ($first->acc_ofcr ?? '')) : null,
            'segment'              => $first ? trim((string) ($first->business_segment_name ?? '')) : null,
            'mis_code'             => $first ? strtoupper(trim((string) ($first->etibiseg2 ?? ''))) : null,
            'code_desc'            => $first ? trim((string) ($first->code_desc ?? '')) : null,
            'business'             => $first ? trim((string) ($first->business ?? '')) : null,
            'accounts'             => $accounts->map(fn($a) => [
                'account_number' => $a->cust_ac_no ?? $a->f12_ac_no,
                'description'    => $a->ac_desc,
                'account_class'  => $a->account_class,
                'branch_code'    => $a->branch_code,
                'open_date'      => $a->ac_open_date,
                'status'         => $a->record_stat,
            ])->values()->all(),
            'has_loan'             => ! empty($loans),
            'loans'                => $loans,
        ];
    }

    private function loans(string $cif): array
    {
        $latestDate = DB::table('loan_listings')
            ->where('cif', $cif)
            ->max('as_at_date');

        if (! $latestDate) {
            return [];
        }

        return DB::table('loan_listings')
            ->where('cif', $cif)
            ->where('as_at_date', $latestDate)
            ->orderBy('related_account')
            ->get([
                'related_account',
                'product_code',
                'loan_status',
                'status_bucket',
                'outstanding_amount_lcy',
                'currency',
                'branch',
                'as_at_date',
            ])
            ->map(fn($l) => [
                'account'         => $l->related_account,
                'product_code'    => $l->product_code,
                'loan_status'     => $l->loan_status,
                'status_bucket'   => $l->status_bucket,
                'outstanding_lcy' => round((float) $l->outstanding_amount_lcy, 2),
                'currency'        => $l->currency,
                'branch'          => $l->branch,
                'as_at_date'      => $l->as_at_date,
            ])
            ->values()
            ->all();
    }

    public function trend(string $cif, ?string $from = null, ?string $to = null): array
    {
        $cif = trim($cif);

        $query = DB::table('customer_balances')
            ->where('cif', $cif)
            ->whereNotNull('balance_date')
            ->select('balance_date', DB::raw('SUM(GREATEST(COALESCE(lcy_balance, balance, 0), 0)) AS lcy_balance'))
            ->groupBy('balance_date')
            ->orderBy('balance_date');

        if ($from) {
            $query->whereDate('balance_date', '>=', Carbon::parse($from)->toDateString());
        }

        if ($to) {
            $query->whereDate('balance_date', '<=', Carbon::parse($to)->toDateString());
        }

        $rows = $query->get();

        return [
            'labels'   => $rows->pluck('balance_date')->map(fn($d) => Carbon::parse($d)->format('d M Y'))->all(),
            'dates'    => $rows->pluck('balance_date')->all(),
            'balances' => $rows->pluck('lcy_balance')->map(fn($v) => round((float) $v, 2))->all(),
        ];
    }

    public function summary(string $cif): array
    {
        $cif = trim($cif);

        $rows = DB::table('customer_balances')
            ->where('cif', $cif)
            ->whereNotNull('balance_date')
            ->select('balance_date', DB::raw('SUM(GREATEST(COALESCE(lcy_balance, balance, 0), 0)) AS lcy_balance'))
            ->groupBy('balance_date')
            ->orderBy('balance_date')
            ->get()
            ->keyBy(fn($r) => Carbon::parse($r->balance_date)->toDateString());

        if ($rows->isEmpty()) {
            return $this->emptyCards();
        }

        $dates = $rows->keys()->sort()->values()->all();
        $latestDate = last($dates);
        $current = (float) ($rows[$latestDate]->lcy_balance ?? 0);

        $dailyDate = $this->latestDateBefore($dates, $latestDate);
        $mtdDate   = $this->latestDateOnOrBefore($dates, Carbon::parse($latestDate)->startOfMonth()->subDay()->toDateString());
        $ytdDate   = $this->latestDateOnOrBefore($dates, Carbon::parse($latestDate)->startOfYear()->subDay()->toDateString());

        $dailyPrev = $dailyDate ? (float) ($rows[$dailyDate]->lcy_balance ?? 0) : 0.0;
        $mtdPrev   = $mtdDate   ? (float) ($rows[$mtdDate]->lcy_balance   ?? 0) : 0.0;
        $ytdPrev   = $ytdDate   ? (float) ($rows[$ytdDate]->lcy_balance   ?? 0) : 0.0;

        return [
            'as_of_date'     => $latestDate,
            'current_balance'=> round($current, 2),
            'daily_movement' => round($current - $dailyPrev, 2),
            'mtd_movement'   => round($current - $mtdPrev, 2),
            'ytd_movement'   => round($current - $ytdPrev, 2),
            'daily_from'     => $dailyDate,
            'mtd_from'       => $mtdDate,
            'ytd_from'       => $ytdDate,
            'data_points'    => count($dates),
        ];
    }

    private function emptyCards(): array
    {
        return [
            'as_of_date'      => null,
            'current_balance' => 0.0,
            'daily_movement'  => 0.0,
            'mtd_movement'    => 0.0,
            'ytd_movement'    => 0.0,
            'daily_from'      => null,
            'mtd_from'        => null,
            'ytd_from'        => null,
            'data_points'     => 0,
        ];
    }

    private function resolveCustomerName(string $cif): ?string
    {
        $sources = [
            ['table' => 'customer_balances',         'cif' => 'cif',      'cols' => ['customer_name', 'account_name', 'account_title', 'name']],
            ['table' => 'customer_accounts_imports',  'cif' => 'f12_cif',  'cols' => ['customer_name', 'account_name', 'ac_desc', 'name']],
        ];

        foreach ($sources as $src) {
            if (! Schema::hasTable($src['table'])) {
                continue;
            }

            $nameCol = collect($src['cols'])->first(fn($c) => Schema::hasColumn($src['table'], $c));

            if (! $nameCol) {
                continue;
            }

            $name = DB::table($src['table'])
                ->where($src['cif'], $cif)
                ->whereNotNull($nameCol)
                ->whereRaw("TRIM(`{$nameCol}`) <> ''")
                ->orderByDesc('id')
                ->value(DB::raw("TRIM(`{$nameCol}`)"));

            if ($name) {
                return trim((string) $name);
            }
        }

        return null;
    }

    private function latestDateBefore(array $dates, string $target): ?string
    {
        $candidate = null;
        foreach ($dates as $d) {
            if ($d < $target) {
                $candidate = $d;
            }
        }
        return $candidate;
    }

    private function latestDateOnOrBefore(array $dates, string $target): ?string
    {
        $candidate = null;
        foreach ($dates as $d) {
            if ($d <= $target) {
                $candidate = $d;
            } else {
                break;
            }
        }
        return $candidate;
    }
}
