<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Finance\RmMover;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RmMoversService
{
    private const EXCLUDED_CR_GL = '216220001';

    private const INCLUDED_EXCEPTION_CIFS = [
        '470000068',
        '470218244',
        '470224763',
        '470090458',
        '470321717',
        '470291487',
        '470317567',
        '470803302',
        '470251434',
    ];

    /**
     * Build RM movers summary for a selected period.
     *
     * RM codes are sourced from:
     * customer_accounts_imports.acc_ofcr
     *
     * CIF mapping is:
     * customer_accounts_imports.f12_cif => customer_balances.cif
     */
    public function build(string $start, string $end): int
    {
        return DB::transaction(function () use ($start, $end) {
            $startDate = Carbon::parse($start)->toDateString();
            $endDate   = Carbon::parse($end)->toDateString();

            $this->guardTables();

            $this->assertBalanceDataExists($startDate, $endDate);

            $this->safeDeleteExisting($startDate, $endDate);

            $balanceSub = $this->balanceSubquery($startDate, $endDate);
            $rmSub      = $this->rmSubquery();

            $rows = DB::query()
                ->fromSub($balanceSub, 'b')
                ->joinSub($rmSub, 'rm', 'rm.cif', '=', 'b.cif')
                ->selectRaw("
                    rm.rm_code,
                    SUM(b.start_balance) AS start_balance,
                    SUM(b.end_balance)   AS end_balance,
                    SUM(b.end_balance - b.start_balance) AS movement,
                    COUNT(DISTINCT b.cif) AS cif_count
                ")
                ->groupBy('rm.rm_code')
                ->orderBy('rm.rm_code')
                ->get();

            if ($rows->isEmpty()) {
                return 0;
            }

            $now = now();

            $payload = $rows->map(function ($row) use ($startDate, $endDate, $now) {
                return [
                    'start_date'    => $startDate,
                    'end_date'      => $endDate,
                    'rm_code'       => (string) $row->rm_code,
                    'start_balance' => round((float) $row->start_balance, 2),
                    'end_balance'   => round((float) $row->end_balance, 2),
                    'movement'      => round((float) $row->movement, 2),
                    'cif_count'     => (int) $row->cif_count,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            })->all();

            foreach (array_chunk($payload, 500) as $chunk) {
                RmMover::insert($chunk);
            }

            return count($payload);
        });
    }

    /**
     * Customer-level drilldown for one RM.
     *
     * Limit is used by the frontend to show top 25, 50, 100, etc.
     */
    public function drilldown(string $start, string $end, string $rmCode, int $limit = 25): array
    {
        $startDate = Carbon::parse($start)->toDateString();
        $endDate   = Carbon::parse($end)->toDateString();

        $limit = max(1, min($limit, 1000));

        $this->guardTables();

        $balanceSub = $this->balanceSubquery($startDate, $endDate);
        $rmSub      = $this->rmSubquery();

        $rows = DB::query()
            ->fromSub($balanceSub, 'b')
            ->joinSub($rmSub, 'rm', 'rm.cif', '=', 'b.cif')
            ->selectRaw("
                b.cif,
                rm.rm_code,
                b.start_balance,
                b.end_balance,
                (b.end_balance - b.start_balance) AS movement
            ")
            ->where('rm.rm_code', strtoupper(trim($rmCode)))
            ->orderByDesc(DB::raw('ABS(b.end_balance - b.start_balance)'))
            ->limit($limit)
            ->get();

        $names = $this->customerNamesByCif(
            $rows->pluck('cif')->map(fn ($c) => (string) $c)->all()
        );

        return $rows->map(function ($row) use ($names) {
            $cif = (string) $row->cif;

            return [
                'cif'           => $cif,
                'customer_name' => $names[$cif] ?? $cif,
                'rm_code'       => (string) $row->rm_code,
                'start_balance' => round((float) $row->start_balance, 2),
                'end_balance'   => round((float) $row->end_balance, 2),
                'movement'      => round((float) $row->movement, 2),
            ];
        })->values()->all();
    }

    /**
     * Optional helper for showing CIF drivers for multiple RMs.
     */
    public function drilldownByRmCodes(
        string $start,
        string $end,
        array $rmCodes,
        int $limit = 20
    ): array {
        $startDate = Carbon::parse($start)->toDateString();
        $endDate   = Carbon::parse($end)->toDateString();

        $this->guardTables();

        $rmCodes = collect($rmCodes)
            ->map(fn ($c) => strtoupper(trim((string) $c)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($rmCodes)) {
            return [
                'gainers' => [],
                'losers'  => [],
            ];
        }

        $limit = max(1, min($limit, 1000));

        return [
            'gainers' => $this->fetchCifDrivers($startDate, $endDate, $rmCodes, 'gain', $limit),
            'losers'  => $this->fetchCifDrivers($startDate, $endDate, $rmCodes, 'loss', $limit),
        ];
    }

    private function fetchCifDrivers(
        string $startDate,
        string $endDate,
        array $rmCodes,
        string $direction,
        int $limit
    ): array {
        $order    = $direction === 'loss' ? 'ASC' : 'DESC';
        $operator = $direction === 'loss' ? '<' : '>';

        $balanceSub = $this->balanceSubquery($startDate, $endDate);
        $rmSub      = $this->rmSubquery();

        $rows = DB::query()
            ->fromSub($balanceSub, 'b')
            ->joinSub($rmSub, 'rm', 'rm.cif', '=', 'b.cif')
            ->selectRaw("
                b.cif,
                MAX(rm.rm_code) AS rm_code,
                SUM(b.start_balance) AS start_balance,
                SUM(b.end_balance)   AS end_balance,
                SUM(b.end_balance - b.start_balance) AS movement
            ")
            ->whereIn('rm.rm_code', $rmCodes)
            ->groupBy('b.cif')
            ->havingRaw("SUM(b.end_balance - b.start_balance) {$operator} 0")
            ->orderByRaw("movement {$order}")
            ->limit($limit)
            ->get();

        $names = $this->customerNamesByCif(
            $rows->pluck('cif')->map(fn ($c) => (string) $c)->all()
        );

        return $rows->map(function ($row) use ($direction, $names) {
            $cif = (string) $row->cif;

            return [
                'cif'           => $cif,
                'customer_name' => $names[$cif] ?? $cif,
                'rm_code'       => (string) $row->rm_code,
                'start_balance' => round((float) $row->start_balance, 2),
                'end_balance'   => round((float) $row->end_balance, 2),
                'movement'      => round((float) $row->movement, 2),
                'direction'     => strtoupper($direction),
            ];
        })->values()->all();
    }

    /**
     * Balance source.
     *
     * This creates one row per CIF with:
     * - start balance on start date
     * - end balance on end date
     */
    private function balanceSubquery(string $startDate, string $endDate)
    {
        return DB::table('customer_balances as cb')
            ->selectRaw("
                TRIM(cb.cif) AS cif,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS start_balance,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS end_balance
            ", [$startDate, $endDate])
            ->whereIn('cb.balance_date', [$startDate, $endDate])
            ->whereNotNull('cb.cif')
            ->whereRaw("TRIM(cb.cif) <> ''")
            ->where(function ($query) {
                $query->whereIn('cb.cif', self::INCLUDED_EXCEPTION_CIFS)
                    ->orWhere(function ($sub) {
                        $sub->whereRaw("UPPER(TRIM(cb.branch_code)) <> 'P50'")
                            ->where(function ($gl) {
                                $gl->whereNull('cb.cr_gl')
                                    ->orWhere('cb.cr_gl', '<>', self::EXCLUDED_CR_GL);
                            });
                    });
            })
            ->groupBy(DB::raw('TRIM(cb.cif)'));
    }

    /**
     * RM mapping source.
     *
     * RM code comes from:
     * customer_accounts_imports.acc_ofcr
     *
     * CIF comes from:
     * customer_accounts_imports.f12_cif
     *
     * Important:
     * This returns one RM code per CIF to avoid duplicate balance joins.
     *
     * If later you have a proper RM master/history table, replace this logic
     * with the latest/current RM mapping per CIF.
     */
    private function rmSubquery()
    {
        return DB::table('customer_accounts_imports as cai')
            ->selectRaw("
                TRIM(cai.f12_cif) AS cif,
                MAX(UPPER(TRIM(cai.acc_ofcr))) AS rm_code
            ")
            ->whereNotNull('cai.f12_cif')
            ->whereNotNull('cai.acc_ofcr')
            ->whereRaw("TRIM(cai.f12_cif) <> ''")
            ->whereRaw("TRIM(cai.acc_ofcr) <> ''")
            ->groupBy(DB::raw('TRIM(cai.f12_cif)'));
    }

    private function safeDeleteExisting(string $startDate, string $endDate): void
    {
        RmMover::query()
            ->whereDate('start_date', $startDate)
            ->whereDate('end_date', $endDate)
            ->delete();
    }

    /**
     * A date with no customer_balances snapshot at all (e.g. a non-business day)
     * would otherwise silently compute every CIF's opening/closing as 0 for that
     * side of the period, making a real balance look like a 0 -> X "new" movement.
     * Fail loudly instead so the caller picks a date that actually has data.
     */
    private function assertBalanceDataExists(string $startDate, string $endDate): void
    {
        $available = DB::table('customer_balances')
            ->whereIn('balance_date', [$startDate, $endDate])
            ->distinct()
            ->pluck('balance_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->all();

        $missing = array_diff([$startDate, $endDate], $available);

        if (! empty($missing)) {
            throw new \RuntimeException(
                'No balance snapshot found for: ' . implode(', ', array_unique($missing))
                . '. Pick a date that has customer balance data (e.g. the nearest business day) before generating.'
            );
        }
    }

    private function guardTables(): void
    {
        $required = [
            'rm_movers',
            'customer_balances',
            'customer_accounts_imports',
        ];

        foreach ($required as $table) {
            if (! Schema::hasTable($table)) {
                throw new \RuntimeException("Required table [{$table}] not found.");
            }
        }
    }

    private function customerNamesByCif(array $cifs): array
    {
        $cifs = collect($cifs)
            ->map(fn ($cif) => trim((string) $cif))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($cifs)) {
            return [];
        }

        $sources = [
            [
                'table'        => 'customer_balances',
                'cif_column'   => 'cif',
                'name_columns' => [
                    'customer_name',
                    'account_name',
                    'account_title',
                    'customer',
                    'name',
                    'short_name',
                    'cust_name',
                ],
            ],
            [
                'table'        => 'customer_accounts_imports',
                'cif_column'   => 'f12_cif',
                'name_columns' => [
                    'customer_name',
                    'account_name',
                    'ac_desc',
                    'account_title',
                    'customer',
                    'name',
                    'short_name',
                    'cust_name',
                ],
            ],
        ];

        foreach ($sources as $source) {
            if (
                ! Schema::hasTable($source['table']) ||
                ! Schema::hasColumn($source['table'], $source['cif_column'])
            ) {
                continue;
            }

            $nameColumn = collect($source['name_columns'])
                ->first(fn ($col) => Schema::hasColumn($source['table'], $col));

            if (! $nameColumn) {
                continue;
            }

            $rows = DB::table($source['table'])
                ->selectRaw("
                    TRIM(`{$source['cif_column']}`) AS cif,
                    MAX(NULLIF(TRIM(`{$nameColumn}`), '')) AS customer_name
                ")
                ->whereIn($source['cif_column'], $cifs)
                ->groupBy(DB::raw("TRIM(`{$source['cif_column']}`)"))
                ->pluck('customer_name', 'cif')
                ->filter()
                ->map(fn ($n) => trim((string) $n))
                ->all();

            if (! empty($rows)) {
                return $rows;
            }
        }

        return [];
    }
}
