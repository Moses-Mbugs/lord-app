<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GroupMoversService
{
    public const TYPE_BRANCH     = 'BRANCH';
    public const TYPE_BRANCH_CIF = 'BRANCH_CIF';

    public const SCOPE_SUMMARY = 'SUMMARY';
    public const SCOPE_TOP     = 'TOP';

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

    private function branchDisplayName(string $branchCode): string
    {
        $b = strtoupper(trim($branchCode));

        if (preg_match('/^P(\d{1,2})$/', $b, $m)) {
            $b = 'P' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
        }

        return match ($b) {
            'P01' => 'P01-TOWERS',
            'P02' => 'P02-MOMBASA MOI AVENUE',
            'P03' => 'P03-PLAZA',
            'P04' => 'P04-WESTMINSTER',
            'P06' => 'P06-THIKA',
            'P07' => 'P07-ELDORET',
            'P08' => 'P08-KISUMU',
            'P09' => 'P09-KISII',
            'P11' => 'P11-INDUSTRIAL AREA',
            'P12' => 'P12-KARATINA',
            'P13' => 'P13-WESTLANDS',
            'P15' => 'P15-NAKURU',
            'P17' => 'P17-NYERI',
            'P22' => 'P22-UPPER HILL',
            'P23' => 'P23-VALLEY ARCADE',
            'P24' => 'P24-KAREN',
            'P25' => 'P25-NYALI',
            'P30' => 'P30-FORTIS OFFICE PARK',
            'P50' => 'P50-HEAD OFFICE',
            '834' => 'Express Accounts',
            '950' => 'Fingo Accounts',
            'ALL' => 'TOTAL',
            default => $b,
        };
    }

    public function buildBranchMovers(
        string $start,
        string $end,
        int $limit = 10,
        bool $nonNegativeOnly = false,
        string $nonNegativeMode = 'end',
        bool $rowNonNegativeOnly = false
    ): void {
        $startDate = Carbon::parse($start)->toDateString();
        $endDate   = Carbon::parse($end)->toDateString();
        $limit     = max(1, (int) $limit);

        $nonNegativeMode = strtolower(trim($nonNegativeMode));
        if (!in_array($nonNegativeMode, ['end', 'both'], true)) {
            throw new \InvalidArgumentException("nonNegativeMode must be 'end' or 'both', got: {$nonNegativeMode}");
        }

        if (!Schema::hasTable('group_movers')) {
            throw new \RuntimeException("group_movers table not found. Run migration.");
        }
        if (!Schema::hasTable('customer_balances')) {
            throw new \RuntimeException("customer_balances table not found.");
        }

        $balCol = Schema::hasColumn('customer_balances', 'lcy_balance') ? 'cb.lcy_balance' : 'cb.balance';

        DB::table('group_movers')
            ->where('group_type', self::TYPE_BRANCH)
            ->whereDate('start_date', $startDate)
            ->whereDate('end_date', $endDate)
            ->delete();

        $posBalExpr = "CASE WHEN {$balCol} >= 0 THEN {$balCol} ELSE 0 END";

        $startExpr = "SUM(CASE WHEN cb.balance_date = ? THEN {$posBalExpr} ELSE 0 END)";
        $endExpr   = "SUM(CASE WHEN cb.balance_date = ? THEN {$posBalExpr} ELSE 0 END)";
        $moveExpr  = "({$endExpr} - {$startExpr})";

        $base = DB::table('customer_balances as cb')
            ->selectRaw("UPPER(TRIM(cb.branch_code)) as branch_code")
            ->selectRaw("{$startExpr} as start_balance", [$startDate])
            ->selectRaw("{$endExpr} as end_balance", [$endDate])
            ->selectRaw("{$moveExpr} as movement", [$endDate, $startDate])
            ->whereIn('cb.balance_date', [$startDate, $endDate])
            ->whereNotNull('cb.branch_code');

        $this->applyCommonExclusions($base, 'cb');

        $base->groupByRaw("UPPER(TRIM(cb.branch_code))");

        if ($nonNegativeOnly) {
            $base->havingRaw("{$endExpr} >= 0", [$endDate]);
            if ($nonNegativeMode === 'both') {
                $base->havingRaw("{$startExpr} >= 0", [$startDate]);
            }
        }

        $summaryRows = (clone $base)->orderByRaw("movement DESC")->get();

        $now    = now();
        $insert = [];

        $sumStart = 0.0;
        $sumEnd   = 0.0;
        $sumMove  = 0.0;

        foreach ($summaryRows as $r) {
            $b  = (string) ($r->branch_code ?? '—');
            $sb = (float) ($r->start_balance ?? 0);
            $eb = (float) ($r->end_balance ?? 0);
            $mv = (float) ($r->movement ?? 0);

            $sumStart += $sb;
            $sumEnd   += $eb;
            $sumMove  += $mv;

            $insert[] = [
                'group_type'    => self::TYPE_BRANCH,
                'group_key'     => $b,
                'group_name'    => $this->branchDisplayName($b),
                'start_date'    => $startDate,
                'end_date'      => $endDate,
                'start_balance' => $sb,
                'end_balance'   => $eb,
                'movement'      => $mv,
                'scope'         => self::SCOPE_SUMMARY,
                'direction'     => null,
                'rank'          => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        $insert[] = [
            'group_type'    => self::TYPE_BRANCH,
            'group_key'     => 'ALL',
            'group_name'    => $this->branchDisplayName('ALL'),
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'start_balance' => $sumStart,
            'end_balance'   => $sumEnd,
            'movement'      => $sumMove,
            'scope'         => self::SCOPE_SUMMARY,
            'direction'     => null,
            'rank'          => null,
            'created_at'    => $now,
            'updated_at'    => $now,
        ];

        $gainers = (clone $base)
            ->havingRaw("{$moveExpr} > 0", [$endDate, $startDate])
            ->orderByRaw("movement DESC")
            ->limit($limit)
            ->get();

        $rank = 1;
        foreach ($gainers as $r) {
            $b  = (string) ($r->branch_code ?? '—');
            $sb = (float) ($r->start_balance ?? 0);
            $eb = (float) ($r->end_balance ?? 0);
            $mv = (float) ($r->movement ?? 0);

            $insert[] = [
                'group_type'    => self::TYPE_BRANCH,
                'group_key'     => $b,
                'group_name'    => $this->branchDisplayName($b),
                'start_date'    => $startDate,
                'end_date'      => $endDate,
                'start_balance' => $sb,
                'end_balance'   => $eb,
                'movement'      => $mv,
                'scope'         => self::SCOPE_TOP,
                'direction'     => 'GAIN',
                'rank'          => $rank++,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        $losers = (clone $base)
            ->havingRaw("{$moveExpr} < 0", [$endDate, $startDate])
            ->orderByRaw("movement ASC")
            ->limit($limit)
            ->get();

        $rank = 1;
        foreach ($losers as $r) {
            $b  = (string) ($r->branch_code ?? '—');
            $sb = (float) ($r->start_balance ?? 0);
            $eb = (float) ($r->end_balance ?? 0);
            $mv = (float) ($r->movement ?? 0);

            $insert[] = [
                'group_type'    => self::TYPE_BRANCH,
                'group_key'     => $b,
                'group_name'    => $this->branchDisplayName($b),
                'start_date'    => $startDate,
                'end_date'      => $endDate,
                'start_balance' => $sb,
                'end_balance'   => $eb,
                'movement'      => $mv,
                'scope'         => self::SCOPE_TOP,
                'direction'     => 'LOSS',
                'rank'          => $rank++,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        foreach (array_chunk($insert, 1000) as $chunk) {
            DB::table('group_movers')->insert($chunk);
        }

        // -------------------------------------------------------
        // Embedded: build branch_cif_movers after group_movers
        // -------------------------------------------------------
        $this->buildBranchCifMovers($startDate, $endDate, $limit);

        $this->buildBranchMoversFromCifAllocation($startDate, $endDate, $limit, $nonNegativeOnly, $nonNegativeMode);
    }

    /**
     * Pre-compute top N CIF gainers/losers per branch and store in branch_cif_movers.
     * Called automatically at the end of buildBranchMovers().
     */
    public function buildBranchCifMovers(
        string $startDate,
        string $endDate,
        int $limit = 10
    ): void {
        if (!Schema::hasTable('branch_cif_movers')) {
            throw new \RuntimeException("branch_cif_movers table not found. Run migration.");
        }

        $limit    = max(1, (int) $limit);
        $balPlain = Schema::hasColumn('customer_balances', 'lcy_balance') ? 'lcy_balance' : 'balance';

        // Clear existing rows for this period
        DB::table('branch_cif_movers')
            ->whereDate('start_date', $startDate)
            ->whereDate('end_date', $endDate)
            ->delete();

        $startExpr = "SUM(CASE WHEN cb.balance_date = ? THEN cb.{$balPlain} ELSE 0 END)";
        $endExpr   = "SUM(CASE WHEN cb.balance_date = ? THEN cb.{$balPlain} ELSE 0 END)";
        $moveExpr  = "({$endExpr} - {$startExpr})";

        // Pull all branch+cif movements for the period (Consumer + Commercial only)
        $raw = DB::table('customer_balances as cb')
            ->selectRaw("UPPER(TRIM(cb.branch_code)) as branch_code")
            ->selectRaw("cb.cif as cif")
            ->selectRaw("MAX(cb.customer_name) as customer_name")
            ->selectRaw("{$startExpr} as start_balance", [$startDate])
            ->selectRaw("{$endExpr} as end_balance", [$endDate])
            ->selectRaw("{$moveExpr} as movement", [$endDate, $startDate])
            ->whereIn('cb.balance_date', [$startDate, $endDate])
            ->whereNotNull('cb.branch_code')
            ->whereNotNull('cb.cif')
            ->whereRaw("UPPER(TRIM(cb.branch_code)) <> 'P50'")
            ->whereNotIn('cb.cif', function ($sub) {
                $sub->from('customer_accounts_imports')
                    ->select('f12_cif')
                    ->whereNotNull('f12_cif')
                    ->whereRaw("UPPER(TRIM(etibiseg2)) LIKE 'CB%'")
                    ->distinct();
            })
            ->groupByRaw("UPPER(TRIM(cb.branch_code))")
            ->groupBy('cb.cif')
            ->havingRaw("{$moveExpr} <> 0", [$endDate, $startDate])
            ->get();

        // Group by branch
        $byBranch = collect($raw)->groupBy(fn($r) => strtoupper(trim((string) $r->branch_code)));

        $now    = now();
        $insert = [];

        foreach ($byBranch as $branchCode => $items) {
            $branchCode  = (string) $branchCode;
            $branchName  = $this->branchDisplayName($branchCode);

            // Top gainers: movement > 0 and end_balance >= 0
            $gainers = $items
                ->filter(fn($r) => (float) $r->movement > 0 && (float) $r->end_balance >= 0)
                ->sortByDesc(fn($r) => (float) $r->movement)
                ->take($limit)
                ->values();

            $rank = 1;
            foreach ($gainers as $r) {
                $insert[] = [
                    'branch_code'   => $branchCode,
                    'branch_name'   => $branchName,
                    'cif'           => (string) $r->cif,
                    'customer_name' => (string) ($r->customer_name ?? ''),
                    'start_date'    => $startDate,
                    'end_date'      => $endDate,
                    'start_balance' => (float) ($r->start_balance ?? 0),
                    'end_balance'   => (float) ($r->end_balance ?? 0),
                    'movement'      => (float) ($r->movement ?? 0),
                    'direction'     => 'GAIN',
                    'rank'          => $rank++,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }

            // Top losers: movement < 0
            $losers = $items
                ->filter(fn($r) => (float) $r->movement < 0)
                ->sortBy(fn($r) => (float) $r->movement) // most negative first
                ->take($limit)
                ->values();

            $rank = 1;
            foreach ($losers as $r) {
                $insert[] = [
                    'branch_code'   => $branchCode,
                    'branch_name'   => $branchName,
                    'cif'           => (string) $r->cif,
                    'customer_name' => (string) ($r->customer_name ?? ''),
                    'start_date'    => $startDate,
                    'end_date'      => $endDate,
                    'start_balance' => (float) ($r->start_balance ?? 0),
                    'end_balance'   => (float) ($r->end_balance ?? 0),
                    'movement'      => (float) ($r->movement ?? 0),
                    'direction'     => 'LOSS',
                    'rank'          => $rank++,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
        }

        foreach (array_chunk($insert, 1000) as $chunk) {
            DB::table('branch_cif_movers')->insert($chunk);
        }
    }

    private function buildBranchMoversFromCifAllocation(
        string $startDate,
        string $endDate,
        int $limit = 10,
        bool $nonNegativeOnly = false,
        string $nonNegativeMode = 'end'
    ): void {
        $limit = max(1, (int) $limit);

        $balPlain = Schema::hasColumn('customer_balances', 'lcy_balance') ? 'lcy_balance' : 'balance';

        DB::table('group_movers')
            ->where('group_type', self::TYPE_BRANCH_CIF)
            ->whereDate('start_date', $startDate)
            ->whereDate('end_date', $endDate)
            ->delete();

        $branchSums = DB::table('customer_balances as cb2')
            ->selectRaw('cb2.cust_ac_no as cust_ac_no')
            ->selectRaw("UPPER(TRIM(cb2.branch_code)) as branch_code")
            ->selectRaw("SUM(CASE WHEN cb2.{$balPlain} >= 0 THEN cb2.{$balPlain} ELSE 0 END) as end_balance_branch")
            ->whereDate('cb2.balance_date', $endDate)
            ->whereNotNull('cb2.cif')
            ->whereNotNull('cb2.cust_ac_no')
            ->whereNotNull('cb2.branch_code');

        $this->applyCommonExclusions($branchSums, 'cb2');

        $branchSums->groupBy('cb2.cust_ac_no')
            ->groupByRaw("UPPER(TRIM(cb2.branch_code))");

        $branchMax = DB::query()
            ->fromSub($branchSums, 'bs')
            ->selectRaw('cust_ac_no, MAX(end_balance_branch) as max_end_balance')
            ->groupBy('cust_ac_no');

        $branchPick = DB::query()
            ->fromSub($branchSums, 'bs')
            ->joinSub($branchMax, 'bm', function ($join) {
                $join->on('bs.cust_ac_no', '=', 'bm.cust_ac_no')
                    ->on('bs.end_balance_branch', '=', 'bm.max_end_balance');
            })
            ->selectRaw('bs.cust_ac_no, MAX(bs.branch_code) as picked_branch_code')
            ->groupBy('bs.cust_ac_no');

        $startExpr = "SUM(CASE WHEN cb.balance_date = ? THEN CASE WHEN cb.{$balPlain} >= 0 THEN cb.{$balPlain} ELSE 0 END ELSE 0 END)";
        $endExpr   = "SUM(CASE WHEN cb.balance_date = ? THEN CASE WHEN cb.{$balPlain} >= 0 THEN cb.{$balPlain} ELSE 0 END ELSE 0 END)";
        $moveExpr  = "({$endExpr} - {$startExpr})";

        $acctTotals = DB::table('customer_balances as cb')
            ->selectRaw('cb.cust_ac_no as cust_ac_no')
            ->selectRaw("{$startExpr} as start_balance", [$startDate])
            ->selectRaw("{$endExpr} as end_balance", [$endDate])
            ->selectRaw("{$moveExpr} as movement", [$endDate, $startDate])
            ->whereIn('cb.balance_date', [$startDate, $endDate])
            ->whereNotNull('cb.cif')
            ->whereNotNull('cb.cust_ac_no')
            ->whereNotNull('cb.branch_code');

        $this->applyCommonExclusions($acctTotals, 'cb');

        $acctTotals->groupBy('cb.cust_ac_no');

        if ($nonNegativeOnly) {
            $acctTotals->havingRaw("{$endExpr} >= 0", [$endDate]);
            if ($nonNegativeMode === 'both') {
                $acctTotals->havingRaw("{$startExpr} >= 0", [$startDate]);
            }
        }

        $base = DB::query()
            ->fromSub($acctTotals, 'at')
            ->joinSub($branchPick, 'bp', function ($join) {
                $join->on('at.cust_ac_no', '=', 'bp.cust_ac_no');
            })
            ->selectRaw('bp.picked_branch_code as branch_code')
            ->selectRaw('SUM(at.start_balance) as start_balance')
            ->selectRaw('SUM(at.end_balance) as end_balance')
            ->selectRaw('SUM(at.movement) as movement')
            ->groupBy('bp.picked_branch_code');

        $summaryRows = (clone $base)->orderByRaw('movement DESC')->get();

        $now    = now();
        $insert = [];

        $sumStart = 0.0;
        $sumEnd   = 0.0;
        $sumMove  = 0.0;

        foreach ($summaryRows as $r) {
            $b  = (string) ($r->branch_code ?? '—');
            $sb = (float) ($r->start_balance ?? 0);
            $eb = (float) ($r->end_balance ?? 0);
            $mv = (float) ($r->movement ?? 0);

            $sumStart += $sb;
            $sumEnd   += $eb;
            $sumMove  += $mv;

            $insert[] = [
                'group_type'    => self::TYPE_BRANCH_CIF,
                'group_key'     => $b,
                'group_name'    => $this->branchDisplayName($b),
                'start_date'    => $startDate,
                'end_date'      => $endDate,
                'start_balance' => $sb,
                'end_balance'   => $eb,
                'movement'      => $mv,
                'scope'         => self::SCOPE_SUMMARY,
                'direction'     => null,
                'rank'          => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        $insert[] = [
            'group_type'    => self::TYPE_BRANCH_CIF,
            'group_key'     => 'ALL',
            'group_name'    => $this->branchDisplayName('ALL'),
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'start_balance' => $sumStart,
            'end_balance'   => $sumEnd,
            'movement'      => $sumMove,
            'scope'         => self::SCOPE_SUMMARY,
            'direction'     => null,
            'rank'          => null,
            'created_at'    => $now,
            'updated_at'    => $now,
        ];

        $gainers = (clone $base)
            ->havingRaw('SUM(at.movement) > 0')
            ->orderByRaw('movement DESC')
            ->limit($limit)
            ->get();

        $rank = 1;
        foreach ($gainers as $r) {
            $b  = (string) ($r->branch_code ?? '—');
            $sb = (float) ($r->start_balance ?? 0);
            $eb = (float) ($r->end_balance ?? 0);
            $mv = (float) ($r->movement ?? 0);

            $insert[] = [
                'group_type'    => self::TYPE_BRANCH_CIF,
                'group_key'     => $b,
                'group_name'    => $this->branchDisplayName($b),
                'start_date'    => $startDate,
                'end_date'      => $endDate,
                'start_balance' => $sb,
                'end_balance'   => $eb,
                'movement'      => $mv,
                'scope'         => self::SCOPE_TOP,
                'direction'     => 'GAIN',
                'rank'          => $rank++,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        $losers = (clone $base)
            ->havingRaw('SUM(at.movement) < 0')
            ->orderByRaw('movement ASC')
            ->limit($limit)
            ->get();

        $rank = 1;
        foreach ($losers as $r) {
            $b  = (string) ($r->branch_code ?? '—');
            $sb = (float) ($r->start_balance ?? 0);
            $eb = (float) ($r->end_balance ?? 0);
            $mv = (float) ($r->movement ?? 0);

            $insert[] = [
                'group_type'    => self::TYPE_BRANCH_CIF,
                'group_key'     => $b,
                'group_name'    => $this->branchDisplayName($b),
                'start_date'    => $startDate,
                'end_date'      => $endDate,
                'start_balance' => $sb,
                'end_balance'   => $eb,
                'movement'      => $mv,
                'scope'         => self::SCOPE_TOP,
                'direction'     => 'LOSS',
                'rank'          => $rank++,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        foreach (array_chunk($insert, 1000) as $chunk) {
            DB::table('group_movers')->insert($chunk);
        }
    }

    private function applyCommonExclusions($query, string $alias = 'cb'): void
    {
        $query->where(function ($main) use ($alias) {
            $main->whereIn("{$alias}.cif", self::INCLUDED_EXCEPTION_CIFS)
                ->orWhere(function ($normal) use ($alias) {
                    $normal->whereRaw("UPPER(TRIM({$alias}.branch_code)) <> 'P50'");

                    if (Schema::hasColumn('customer_balances', 'cr_gl')) {
                        $normal->where(function ($w) use ($alias) {
                            $w->whereNull("{$alias}.cr_gl")
                                ->orWhere("{$alias}.cr_gl", '<>', self::EXCLUDED_CR_GL);
                        });
                    }

                    // Exclude Corporate Banking (etibiseg2 starting with 'CB')
                    $normal->whereNotIn("{$alias}.cif", function ($sub) {
                        $sub->from('customer_accounts_imports')
                            ->select('f12_cif')
                            ->whereNotNull('f12_cif')
                            ->whereRaw("UPPER(TRIM(etibiseg2)) LIKE 'CB%'")
                            ->distinct();
                    });
                });
        });
    }
}
