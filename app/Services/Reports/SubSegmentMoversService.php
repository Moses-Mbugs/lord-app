<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Finance\SubSegmentMover;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SubSegmentMoversService
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

    public function build(string $start, string $end): int
    {
        $startDate = Carbon::parse($start)->toDateString();
        $endDate   = Carbon::parse($end)->toDateString();

        $this->guardTables();

        $this->safeDeleteExisting($startDate, $endDate);

        $balanceSub = $this->balanceSubquery($startDate, $endDate);
        $cifMisSub  = $this->cifMisCodeSubquery();

        $rows = DB::query()
            ->fromSub($balanceSub, 'b')
            ->leftJoinSub($cifMisSub, 'cm', function ($join) {
                $join->on('cm.cif', '=', 'b.cif');
            })
            ->leftJoin('sub_segment_mappings as sm', 'sm.mis_code', '=', 'cm.mis_code')
            ->selectRaw("
                COALESCE(sm.business, 'UNMAPPED') AS business,
                COALESCE(sm.business_segment_name, 'UNMAPPED') AS business_segment_name,
                COALESCE(sm.business_seg_short, 'UNMAPPED') AS business_seg_short,
                COALESCE(sm.mis_code, cm.mis_code, 'UNMAPPED') AS mis_code,
                COALESCE(sm.code_desc, 'Unmapped Sub Segment') AS code_desc,
                SUM(b.start_balance) AS start_balance,
                SUM(b.end_balance) AS end_balance,
                SUM(b.end_balance - b.start_balance) AS movement,
                COUNT(DISTINCT b.cif) AS cif_count
            ")
            ->groupBy(DB::raw("COALESCE(sm.business, 'UNMAPPED')"))
            ->groupBy(DB::raw("COALESCE(sm.business_segment_name, 'UNMAPPED')"))
            ->groupBy(DB::raw("COALESCE(sm.business_seg_short, 'UNMAPPED')"))
            ->groupBy(DB::raw("COALESCE(sm.mis_code, cm.mis_code, 'UNMAPPED')"))
            ->groupBy(DB::raw("COALESCE(sm.code_desc, 'Unmapped Sub Segment')"))
            ->orderBy('business')
            ->orderBy('business_segment_name')
            ->orderBy('mis_code')
            ->get();

        if ($rows->isEmpty()) {
            return 0;
        }

        $now = now();

        $payload = $rows->map(function ($row) use ($startDate, $endDate, $now) {
            return [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'business' => (string) $row->business,
                'business_segment_name' => (string) $row->business_segment_name,
                'business_seg_short' => (string) $row->business_seg_short,
                'mis_code' => (string) $row->mis_code,
                'code_desc' => (string) $row->code_desc,
                'start_balance' => (float) $row->start_balance,
                'end_balance' => (float) $row->end_balance,
                'movement' => (float) $row->movement,
                'cif_count' => (int) $row->cif_count,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        foreach (array_chunk($payload, 500) as $chunk) {
            SubSegmentMover::insert($chunk);
        }

        return count($payload);
    }

    public function drilldown(string $start, string $end, string $misCode, int $limit = 1000): array
    {
        $startDate = Carbon::parse($start)->toDateString();
        $endDate   = Carbon::parse($end)->toDateString();

        $this->guardTables();

        $balanceSub = $this->balanceSubquery($startDate, $endDate);
        $cifMisSub  = $this->cifMisCodeSubquery();

        return DB::query()
            ->fromSub($balanceSub, 'b')
            ->leftJoinSub($cifMisSub, 'cm', function ($join) {
                $join->on('cm.cif', '=', 'b.cif');
            })
            ->leftJoin('sub_segment_mappings as sm', 'sm.mis_code', '=', 'cm.mis_code')
            ->selectRaw("
                b.cif,
                COALESCE(sm.business, 'UNMAPPED') AS business,
                COALESCE(sm.business_segment_name, 'UNMAPPED') AS business_segment_name,
                COALESCE(sm.business_seg_short, 'UNMAPPED') AS business_seg_short,
                COALESCE(sm.mis_code, cm.mis_code, 'UNMAPPED') AS mis_code,
                COALESCE(sm.code_desc, 'Unmapped Sub Segment') AS code_desc,
                b.start_balance,
                b.end_balance,
                (b.end_balance - b.start_balance) AS movement
            ")
            ->whereRaw("COALESCE(sm.mis_code, cm.mis_code, 'UNMAPPED') = ?", [$misCode])
            ->orderByDesc(DB::raw('(b.end_balance - b.start_balance)'))
            ->limit($limit)
            ->get()
            ->map(fn($row) => (array) $row)
            ->all();
    }

    public function drilldownByMisCodes(
        string $start,
        string $end,
        array $misCodes,
        int $limit = 20
    ): array {
        $startDate = Carbon::parse($start)->toDateString();
        $endDate   = Carbon::parse($end)->toDateString();

        $this->guardTables();

        $misCodes = collect($misCodes)
            ->map(fn($code) => strtoupper(trim((string) $code)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($misCodes)) {
            return [
                'gainers' => [],
                'losers' => [],
            ];
        }

        $gainers = $this->fetchCifDriversByDirection($startDate, $endDate, $misCodes, 'gain', $limit);
        $losers  = $this->fetchCifDriversByDirection($startDate, $endDate, $misCodes, 'loss', $limit);

        return [
            'gainers' => $gainers,
            'losers' => $losers,
        ];
    }

    private function fetchCifDriversByDirection(
        string $startDate,
        string $endDate,
        array $misCodes,
        string $direction,
        int $limit
    ): array {
        $order = $direction === 'loss' ? 'ASC' : 'DESC';
        $operator = $direction === 'loss' ? '<' : '>';

        $balanceSub = $this->balanceSubquery($startDate, $endDate);
        $cifMisSub  = $this->cifMisCodeSubquery();

        $rows = DB::query()
            ->fromSub($balanceSub, 'b')
            ->leftJoinSub($cifMisSub, 'cm', function ($join) {
                $join->on('cm.cif', '=', 'b.cif');
            })
            ->leftJoin('sub_segment_mappings as sm', 'sm.mis_code', '=', 'cm.mis_code')
            ->selectRaw("
            b.cif,
            MAX(COALESCE(sm.business, 'UNMAPPED')) AS business,
            MAX(COALESCE(sm.business_segment_name, 'UNMAPPED')) AS business_segment_name,
            MAX(COALESCE(sm.business_seg_short, 'UNMAPPED')) AS business_seg_short,
            MAX(COALESCE(sm.mis_code, cm.mis_code, 'UNMAPPED')) AS mis_code,
            MAX(COALESCE(sm.code_desc, 'Unmapped Sub Segment')) AS code_desc,
            SUM(b.start_balance) AS start_balance,
            SUM(b.end_balance) AS end_balance,
            SUM(b.end_balance - b.start_balance) AS movement
        ")
            ->whereIn(DB::raw("COALESCE(sm.mis_code, cm.mis_code, 'UNMAPPED')"), $misCodes)
            ->groupBy('b.cif')
            ->havingRaw("SUM(b.end_balance - b.start_balance) {$operator} 0")
            ->orderByRaw("movement {$order}")
            ->limit($limit)
            ->get();

        $names = $this->customerNamesByCif(
            $rows->pluck('cif')->map(fn($cif) => (string) $cif)->all()
        );

        return $rows
            ->map(function ($row) use ($direction, $names) {
                $cif = (string) $row->cif;

                return [
                    'customer_name' => $names[$cif] ?? $cif,
                    'business' => (string) $row->business,
                    'business_segment_name' => (string) $row->business_segment_name,
                    'business_seg_short' => (string) $row->business_seg_short,
                    'mis_code' => (string) $row->mis_code,
                    'code_desc' => (string) $row->code_desc,
                    'start_balance' => round((float) $row->start_balance, 2),
                    'end_balance' => round((float) $row->end_balance, 2),
                    'movement' => round((float) $row->movement, 2),
                    'direction' => strtoupper($direction),
                ];
            })
            ->values()
            ->all();
    }

    private function balanceSubquery(string $startDate, string $endDate)
    {
        return DB::table('customer_balances as cb')
            ->selectRaw("
                cb.cif,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS start_balance,
                SUM(CASE WHEN cb.balance_date = ? THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS end_balance
            ", [$startDate, $endDate])
            ->whereIn('cb.balance_date', [$startDate, $endDate])
            ->whereNotNull('cb.cif')
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
            ->groupBy('cb.cif');
    }

    private function cifMisCodeSubquery()
    {
        return DB::table('customer_accounts_imports as cai')
            ->selectRaw("
                cai.f12_cif AS cif,
                TRIM(cai.etibiseg2) AS mis_code
            ")
            ->whereNotNull('cai.f12_cif')
            ->whereNotNull('cai.etibiseg2')
            ->whereRaw("TRIM(cai.etibiseg2) <> ''")
            ->groupBy('cai.f12_cif', DB::raw('TRIM(cai.etibiseg2)'));
    }

    private function safeDeleteExisting(string $startDate, string $endDate): void
    {
        SubSegmentMover::query()
            ->whereDate('start_date', $startDate)
            ->whereDate('end_date', $endDate)
            ->delete();
    }

    private function guardTables(): void
    {
        $required = [
            'sub_segment_mappings',
            'sub_segment_movers',
            'customer_balances',
            'customer_accounts_imports',
        ];

        foreach ($required as $table) {
            if (! Schema::hasTable($table)) {
                throw new \RuntimeException("Required table [{$table}] not found.");
            }
        }
    }

    private function customerNameAggregateExpression(string $table, string $alias): string
    {
        $candidateColumns = [
            'customer_name',
            'account_name',
            'account_title',
            'customer',
            'name',
            'short_name',
            'cust_name',
        ];

        foreach ($candidateColumns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return "MAX(NULLIF(TRIM({$alias}.`{$column}`), ''))";
            }
        }

        return 'NULL';
    }

    private function customerNamesByCif(array $cifs): array
    {
        $cifs = collect($cifs)
            ->map(fn($cif) => trim((string) $cif))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($cifs)) {
            return [];
        }

        $sources = [
            [
                'table' => 'customer_balances',
                'cif_column' => 'cif',
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
                'table' => 'customer_accounts_imports',
                'cif_column' => 'f12_cif',
                'name_columns' => [
                    'customer_name',
                    'account_name',
                    'account_title',
                    'customer',
                    'name',
                    'short_name',
                    'cust_name',
                    'f13_customer_name',
                    'f14_customer_name',
                ],
            ],
        ];

        foreach ($sources as $source) {
            if (!Schema::hasTable($source['table']) || !Schema::hasColumn($source['table'], $source['cif_column'])) {
                continue;
            }

            $nameColumn = collect($source['name_columns'])
                ->first(fn($column) => Schema::hasColumn($source['table'], $column));

            if (!$nameColumn) {
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
                ->map(fn($name) => trim((string) $name))
                ->all();

            if (!empty($rows)) {
                return $rows;
            }
        }

        return [];
    }
}
