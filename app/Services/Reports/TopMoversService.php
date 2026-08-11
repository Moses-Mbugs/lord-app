<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TopMoversService
{
    public const SCOPE_CIF_CURRENCY = 'cif_currency';
    public const SCOPE_CIF_ONLY     = 'cif_only';
    public const SCOPE_TOP_BALANCES = 'top_balances';

    // exclude this GL like branch P50
    private const EXCLUDED_CR_GL = '216220001';

    /**
     * CIFs that should bypass the normal exclusion rules.
     * Even if they are under P50 or cr_gl = 216220001, they must still be included.
     */
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
     * Build top movers between two dates.
     *
     * $nonNegativeOnly:
     *  - if true: require balances >= 0 (based on $nonNegativeMode)
     * $nonNegativeMode:
     *  - 'end'  => only end_balance must be >= 0
     *  - 'both' => both start_balance and end_balance must be >= 0
     */
    public function build(
        string $start,
        string $end,
        string $currencyType = 'LCY',
        int $limit = 20,
        string $scope = self::SCOPE_CIF_CURRENCY,
        bool $nonNegativeOnly = false,
        string $nonNegativeMode = 'end'
    ): void {
        $startDate = Carbon::parse($start)->toDateString();
        $endDate   = Carbon::parse($end)->toDateString();

        $currencyType = strtoupper(trim($currencyType));
        $scope = strtolower(trim($scope));
        $nonNegativeMode = strtolower(trim($nonNegativeMode));

        if (!Schema::hasTable('top_movers')) {
            throw new \RuntimeException("top_movers table not found.");
        }

        if (!in_array($scope, [self::SCOPE_CIF_CURRENCY, self::SCOPE_CIF_ONLY], true)) {
            throw new \InvalidArgumentException("scope must be cif_currency or cif_only, got: {$scope}");
        }

        if ($scope === self::SCOPE_CIF_CURRENCY && !in_array($currencyType, ['LCY', 'FCY'], true)) {
            throw new \InvalidArgumentException("currencyType must be LCY or FCY, got: {$currencyType}");
        }

        if (!in_array($nonNegativeMode, ['end', 'both'], true)) {
            throw new \InvalidArgumentException("nonNegativeMode must be 'end' or 'both', got: {$nonNegativeMode}");
        }

        $limit = max(1, (int) $limit);
        $now = now();

        // delete previous rows for this exact run
        $this->safeDeleteExistingTopMovers($startDate, $endDate, $currencyType, $scope);

        if ($scope === self::SCOPE_CIF_ONLY) {
            $gainers = $this->fetchMoversCifOnly($startDate, $endDate, $limit, 'gain', $nonNegativeOnly, $nonNegativeMode);
            $losers  = $this->fetchMoversCifOnly($startDate, $endDate, $limit, 'loss', $nonNegativeOnly, $nonNegativeMode);

            $rows = array_merge($gainers, $losers);
            if (!empty($rows)) {
                $this->insertTopMovers($rows, $now);
            }
            return;
        }

        // cif + currency
        $gainers = $this->fetchMoversPerCurrency($startDate, $endDate, $currencyType, $limit, 'gain', $nonNegativeOnly, $nonNegativeMode);
        $losers  = $this->fetchMoversPerCurrency($startDate, $endDate, $currencyType, $limit, 'loss', $nonNegativeOnly, $nonNegativeMode);

        $rows = array_merge($gainers, $losers);
        if (!empty($rows)) {
            $this->insertTopMovers($rows, $now);
        }
    }

    /**
     * Top balances snapshot.
     * - ranks CIFs by total LCY balance on ONE date
     * - optional: only non-negative totals
     */
    public function buildTopBalances(
        string $asOf,
        int $limit = 20,
        bool $nonNegativeOnly = true
    ): void {
        $asOfDate = Carbon::parse($asOf)->toDateString();

        if (!Schema::hasTable('top_movers')) {
            throw new \RuntimeException("top_movers table not found.");
        }

        $limit = max(1, (int) $limit);
        $now = now();

        // delete previous snapshot rows for this date
        $this->safeDeleteExistingTopMovers($asOfDate, $asOfDate, 'LCY', self::SCOPE_TOP_BALANCES);

        $balanceCol = Schema::hasColumn('customer_balances', 'lcy_balance') ? 'cb.lcy_balance' : 'cb.balance';

        $q = DB::table('customer_balances as cb')
            ->selectRaw('cb.cif as cif')
            ->selectRaw('MAX(cb.customer_name) as customer_name')
            ->selectRaw('MAX(cb.branch_code) as branch_code')
            ->selectRaw("SUM({$balanceCol}) as total_lcy_balance")
            ->whereDate('cb.balance_date', $asOfDate)
            ->whereNotNull('cb.cif');

        $this->applyCommonExclusions($q, 'cb');

        $q->groupBy('cb.cif');

        if ($nonNegativeOnly) {
            $q->havingRaw("SUM({$balanceCol}) >= 0");
        }

        $rows = $q->orderByDesc('total_lcy_balance')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) return;

        $out = [];
        foreach ($rows as $r) {
            $total = (string) ($r->total_lcy_balance ?? '0');
            $out[] = [
                'start_date'     => $asOfDate,
                'end_date'       => $asOfDate,
                'currency_type'  => 'LCY',
                'scope'          => self::SCOPE_TOP_BALANCES,
                'cif'            => (string) ($r->cif ?? ''),
                'customer_name'  => (string) ($r->customer_name ?? ''),
                'currency'       => 'KES',
                'branch_code'    => (string) ($r->branch_code ?? ''),
                'cust_ac_no'     => null,
                'start_balance'  => $total,
                'end_balance'    => $total,
                'movement'       => '0',
                'direction'      => 'BALANCE',
            ];
        }

        $this->insertTopMovers($out, $now);
    }

    // -------------------------------------------------------------------------
    // Dashboard read model — shared by the "Daily Deposits Movement" email
    // (EmailTopMoversCommand) and the Top Movers dashboard page, so both render
    // the exact same numbers from the same persisted snapshot.
    // -------------------------------------------------------------------------

    /**
     * Most recent persisted top_movers snapshot window (whatever the last
     * `reports:build-top-movers` run covered), regardless of scope/currency.
     *
     * @return array{start: string, end: string}|null
     */
    public function latestSnapshotWindow(): ?array
    {
        $end = DB::table('top_movers')->max('end_date');
        if (!$end) {
            return null;
        }
        $end = Carbon::parse($end)->toDateString();

        $start = DB::table('top_movers')->whereDate('end_date', $end)->max('start_date');
        if (!$start) {
            return null;
        }

        return ['start' => Carbon::parse($start)->toDateString(), 'end' => $end];
    }

    /**
     * CIF_ONLY + CIF_CURRENCY (LCY/FCY) gainers/losers for a window, with the
     * LCY/FCY balance breakdown attached to each CIF_ONLY row.
     *
     * @return array{
     *     CIF_ONLY: array{GAIN: Collection, LOSS: Collection},
     *     CIF_CURRENCY: array{LCY: array{GAIN: Collection, LOSS: Collection}, FCY: array{GAIN: Collection, LOSS: Collection}}
     * }
     */
    public function fetchGroupedMovers(string $start, string $end, int $limit = 20, int $currencyLimit = 10): array
    {
        $rows = DB::table('top_movers')
            ->whereDate('start_date', $start)
            ->whereDate('end_date', $end)
            ->orderBy('currency_type')
            ->orderByDesc('movement')
            ->get();

        $scopesPresent = [];
        foreach ($rows as $r) {
            if (isset($r->scope) && $r->scope !== null && $r->scope !== '') {
                $scopesPresent[] = strtolower((string) $r->scope);
            }
        }
        $hasScope = in_array('cif_only', $scopesPresent, true) || in_array('cif_currency', $scopesPresent, true);

        $grouped = [
            'CIF_ONLY' => ['GAIN' => collect(), 'LOSS' => collect()],
            'CIF_CURRENCY' => [
                'LCY' => ['GAIN' => collect(), 'LOSS' => collect()],
                'FCY' => ['GAIN' => collect(), 'LOSS' => collect()],
            ],
        ];

        foreach ($rows as $r) {
            $dir = strtoupper((string) ($r->direction ?? ''));
            if (!in_array($dir, ['GAIN', 'LOSS'], true)) {
                continue;
            }

            $scope = $hasScope ? strtolower((string) ($r->scope ?? '')) : 'cif_currency';

            if ($scope === 'cif_only') {
                if ($grouped['CIF_ONLY'][$dir]->count() < $limit) {
                    $grouped['CIF_ONLY'][$dir]->push($r);
                }
                continue;
            }

            $ct = strtoupper((string) ($r->currency_type ?? ''));
            if (!in_array($ct, ['LCY', 'FCY'], true)) {
                continue;
            }

            if ($grouped['CIF_CURRENCY'][$ct][$dir]->count() < $currencyLimit) {
                $grouped['CIF_CURRENCY'][$ct][$dir]->push($r);
            }
        }

        $allCifRows = $grouped['CIF_ONLY']['GAIN']->merge($grouped['CIF_ONLY']['LOSS']);
        if ($allCifRows->isNotEmpty()) {
            $breakdown = $this->fetchLcyFcyBreakdown($allCifRows->pluck('cif'), $start, $end);
            foreach (['GAIN', 'LOSS'] as $dir) {
                foreach ($grouped['CIF_ONLY'][$dir] as $r) {
                    $b = $breakdown->get((string) ($r->cif ?? ''));
                    $r->lcy_movement = $b ? round((float) $b->lcy_end - (float) $b->lcy_start, 2) : 0;
                    $r->fcy_movement = $b ? round((float) $b->fcy_end - (float) $b->fcy_start, 2) : 0;
                }
            }
        }

        return $grouped;
    }

    /**
     * Segment overview (CB/CM/CS/OT/ALL) for a window, with LCY/FCY movement
     * breakdown attached — same rows shown in the email's Segment Overview table.
     */
    public function fetchSegmentOverview(string $start, string $end): Collection
    {
        if (!Schema::hasTable('segment_movers')) {
            return collect();
        }

        $order = ['CS' => 1, 'CB' => 2, 'CM' => 3, 'OT' => 4, 'ALL' => 99];

        $segments = DB::table('segment_movers')
            ->whereDate('start_date', $start)
            ->whereDate('end_date', $end)
            ->get()
            ->sortBy(function ($r) use ($order) {
                return $order[strtoupper((string) ($r->segment_code ?? ''))] ?? 50;
            })
            ->values();

        if ($segments->isEmpty()) {
            return $segments;
        }

        $breakdown = $this->fetchSegmentLcyFcyBreakdown($start, $end);

        foreach ($segments as $s) {
            $code = strtoupper((string) ($s->segment_code ?? 'OT'));
            $b    = $breakdown->get($code);

            if (!$b && $code === 'ALL') {
                $s->lcy_movement = round((float) $breakdown->where('segment_code', '!=', 'ALL')->sum('lcy_movement'), 2);
                $s->fcy_movement = round((float) $breakdown->where('segment_code', '!=', 'ALL')->sum('fcy_movement'), 2);
            } else {
                $s->lcy_movement = $b ? round((float) $b->lcy_movement, 2) : 0;
                $s->fcy_movement = $b ? round((float) $b->fcy_movement, 2) : 0;
            }
        }

        return $segments;
    }

    private function fetchSegmentLcyFcyBreakdown(string $start, string $end): Collection
    {
        $exceptionPh = implode(',', array_fill(0, count(self::INCLUDED_EXCEPTION_CIFS), '?'));

        $rows = DB::select("
            SELECT
                COALESCE(s.segment_code, 'OT') AS segment_code,
                SUM(CASE WHEN cb.balance_date = ? AND UPPER(TRIM(cb.currency)) = 'KES'  THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS lcy_start,
                SUM(CASE WHEN cb.balance_date = ? AND UPPER(TRIM(cb.currency)) = 'KES'  THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS lcy_end,
                SUM(CASE WHEN cb.balance_date = ? AND UPPER(TRIM(cb.currency)) != 'KES' THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS fcy_start,
                SUM(CASE WHEN cb.balance_date = ? AND UPPER(TRIM(cb.currency)) != 'KES' THEN GREATEST(cb.lcy_balance, 0) ELSE 0 END) AS fcy_end
            FROM customer_balances cb
            LEFT JOIN (
                SELECT x.cif,
                    CASE
                        WHEN SUM(CASE WHEN x.seg = 'CB' THEN 1 ELSE 0 END) > 0 THEN 'CB'
                        WHEN SUM(CASE WHEN x.seg = 'CM' THEN 1 ELSE 0 END) > 0 THEN 'CM'
                        WHEN SUM(CASE WHEN x.seg = 'CS' THEN 1 ELSE 0 END) > 0 THEN 'CS'
                        ELSE NULL
                    END AS segment_code
                FROM (
                    SELECT f12_cif AS cif,
                        CASE
                            WHEN UPPER(TRIM(etibiseg2)) LIKE 'CB%' THEN 'CB'
                            WHEN UPPER(TRIM(etibiseg2)) LIKE 'CM%' THEN 'CM'
                            WHEN UPPER(TRIM(etibiseg2)) LIKE 'CS%' THEN 'CS'
                            ELSE NULL
                        END AS seg
                    FROM customer_accounts_imports
                    WHERE f12_cif IS NOT NULL AND etibiseg2 IS NOT NULL AND TRIM(etibiseg2) <> ''
                ) x WHERE x.seg IS NOT NULL GROUP BY x.cif
            ) s ON s.cif = cb.cif
            WHERE cb.balance_date IN (?, ?)
              AND cb.cif IS NOT NULL
              AND (
                    cb.cif IN ({$exceptionPh})
                    OR (UPPER(TRIM(cb.branch_code)) != 'P50' AND (cb.cr_gl IS NULL OR cb.cr_gl != ?))
              )
            GROUP BY COALESCE(s.segment_code, 'OT')
        ", array_merge(
            [$start, $end, $start, $end, $start, $end],
            self::INCLUDED_EXCEPTION_CIFS,
            [self::EXCLUDED_CR_GL]
        ));

        return collect($rows)
            ->map(function ($r) {
                $r->lcy_movement = round((float) $r->lcy_end - (float) $r->lcy_start, 2);
                $r->fcy_movement = round((float) $r->fcy_end - (float) $r->fcy_start, 2);
                return $r;
            })
            ->keyBy('segment_code');
    }

    private function fetchLcyFcyBreakdown(Collection $cifs, string $start, string $end): Collection
    {
        if ($cifs->isEmpty()) {
            return collect();
        }

        return DB::table('customer_balances')
            ->selectRaw("
                cif,
                SUM(CASE WHEN balance_date = ? AND UPPER(TRIM(currency)) = 'KES'  THEN GREATEST(lcy_balance, 0) ELSE 0 END) AS lcy_start,
                SUM(CASE WHEN balance_date = ? AND UPPER(TRIM(currency)) = 'KES'  THEN GREATEST(lcy_balance, 0) ELSE 0 END) AS lcy_end,
                SUM(CASE WHEN balance_date = ? AND UPPER(TRIM(currency)) != 'KES' THEN GREATEST(lcy_balance, 0) ELSE 0 END) AS fcy_start,
                SUM(CASE WHEN balance_date = ? AND UPPER(TRIM(currency)) != 'KES' THEN GREATEST(lcy_balance, 0) ELSE 0 END) AS fcy_end
            ", [$start, $end, $start, $end])
            ->whereIn('balance_date', [$start, $end])
            ->whereIn('cif', $cifs->toArray())
            ->whereNotNull('cif')
            ->groupBy('cif')
            ->get()
            ->keyBy('cif');
    }

    private function fetchMoversPerCurrency(
        string $startDate,
        string $endDate,
        string $currencyType,
        int $limit,
        string $type, // gain|loss
        bool $nonNegativeOnly,
        string $nonNegativeMode
    ): array {
        $type = strtolower($type);
        $order = $type === 'gain' ? 'DESC' : 'ASC';
        $direction = $type === 'gain' ? 'GAIN' : 'LOSS';

        $balanceCol = Schema::hasColumn('customer_balances', 'acy_balance') ? 'cb.acy_balance' : 'cb.balance';

        $startExpr = "SUM(CASE WHEN cb.balance_date = ? THEN {$balanceCol} ELSE 0 END)";
        $endExpr   = "SUM(CASE WHEN cb.balance_date = ? THEN {$balanceCol} ELSE 0 END)";
        $moveExpr  = "({$endExpr} - {$startExpr})";

        $q = DB::table('customer_balances as cb')
            ->selectRaw('cb.cif as cif')
            ->selectRaw('MAX(cb.customer_name) as customer_name')
            ->selectRaw('cb.currency as currency')
            ->selectRaw('MAX(cb.branch_code) as branch_code')
            ->selectRaw('MAX(cb.cust_ac_no) as cust_ac_no')
            ->selectRaw("{$startExpr} as start_balance", [$startDate])
            ->selectRaw("{$endExpr} as end_balance", [$endDate])
            ->selectRaw("{$moveExpr} as movement", [$endDate, $startDate])
            ->whereIn('cb.balance_date', [$startDate, $endDate])
            ->whereNotNull('cb.cif')
            ->whereNotNull('cb.currency');

        $this->joinSubSegment($q, 'cb');

        $this->applyCommonExclusions($q, 'cb');

        $q->groupBy('cb.cif', 'cb.currency')
            ->havingRaw("{$moveExpr} <> 0", [$endDate, $startDate]);

        if ($currencyType === 'LCY') {
            $q->whereRaw("UPPER(TRIM(cb.currency)) = 'KES'");
        } else {
            $q->whereRaw("UPPER(TRIM(cb.currency)) <> 'KES'");
        }

        if ($nonNegativeOnly) {
            $q->havingRaw("{$endExpr} >= 0", [$endDate]);
            if ($nonNegativeMode === 'both') {
                $q->havingRaw("{$startExpr} >= 0", [$startDate]);
            }
        } else {
            if ($type === 'gain') {
                $q->havingRaw("{$endExpr} >= 0", [$endDate]);
            }
        }

        if ($type === 'gain') {
            $q->havingRaw("{$moveExpr} > 0", [$endDate, $startDate]);
        } else {
            $q->havingRaw("{$moveExpr} < 0", [$endDate, $startDate]);
        }

        $rows = $q->orderByRaw("movement {$order}")
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) return [];

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'start_date'     => $startDate,
                'end_date'       => $endDate,
                'currency_type'  => $currencyType,
                'scope'          => self::SCOPE_CIF_CURRENCY,
                'cif'            => (string) ($r->cif ?? ''),
                'customer_name'  => (string) ($r->customer_name ?? ''),
                'currency'       => (string) ($r->currency ?? ''),
                'branch_code'    => (string) ($r->branch_code ?? ''),
                'sub_segment'    => (string) ($r->sub_segment ?? 'UNMAPPED'),
                'cust_ac_no'     => (string) ($r->cust_ac_no ?? ''),
                'start_balance'  => (string) ($r->start_balance ?? '0'),
                'end_balance'    => (string) ($r->end_balance ?? '0'),
                'movement'       => (string) ($r->movement ?? '0'),
                'direction'      => $direction,
            ];
        }

        return $out;
    }

    private function fetchMoversCifOnly(
        string $startDate,
        string $endDate,
        int $limit,
        string $type, // gain|loss
        bool $nonNegativeOnly,
        string $nonNegativeMode
    ): array {
        $type = strtolower($type);
        $order = $type === 'gain' ? 'DESC' : 'ASC';
        $direction = $type === 'gain' ? 'GAIN' : 'LOSS';

        $balanceCol = Schema::hasColumn('customer_balances', 'lcy_balance') ? 'cb.lcy_balance' : 'cb.balance';

        $startExpr = "SUM(CASE WHEN cb.balance_date = ? THEN {$balanceCol} ELSE 0 END)";
        $endExpr   = "SUM(CASE WHEN cb.balance_date = ? THEN {$balanceCol} ELSE 0 END)";
        $moveExpr  = "({$endExpr} - {$startExpr})";

        $q = DB::table('customer_balances as cb')
            ->selectRaw('cb.cif as cif')
            ->selectRaw('MAX(cb.customer_name) as customer_name')
            ->selectRaw('MAX(cb.branch_code) as branch_code')
            ->selectRaw("{$startExpr} as start_balance", [$startDate])
            ->selectRaw("{$endExpr} as end_balance", [$endDate])
            ->selectRaw("{$moveExpr} as movement", [$endDate, $startDate])
            ->whereIn('cb.balance_date', [$startDate, $endDate])
            ->whereNotNull('cb.cif');

        $this->joinSubSegment($q, 'cb');

        $this->applyCommonExclusions($q, 'cb');

        $q->groupBy('cb.cif')
            ->havingRaw("{$moveExpr} <> 0", [$endDate, $startDate]);

        if ($nonNegativeOnly) {
            $q->havingRaw("{$endExpr} >= 0", [$endDate]);
            if ($nonNegativeMode === 'both') {
                $q->havingRaw("{$startExpr} >= 0", [$startDate]);
            }
        } else {
            if ($type === 'gain') {
                $q->havingRaw("{$endExpr} >= 0", [$endDate]);
            }
        }

        if ($type === 'gain') {
            $q->havingRaw("{$moveExpr} > 0", [$endDate, $startDate]);
        } else {
            $q->havingRaw("{$moveExpr} < 0", [$endDate, $startDate]);
        }

        $rows = $q->orderByRaw("movement {$order}")
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) return [];

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'start_date'     => $startDate,
                'end_date'       => $endDate,
                'currency_type'  => 'LCY',
                'scope'          => self::SCOPE_CIF_ONLY,
                'cif'            => (string) ($r->cif ?? ''),
                'customer_name'  => (string) ($r->customer_name ?? ''),
                'currency'       => 'KES',
                'branch_code'    => (string) ($r->branch_code ?? ''),
                'sub_segment'    => (string) ($r->sub_segment ?? 'UNMAPPED'),
                'cust_ac_no'     => null,
                'start_balance'  => (string) ($r->start_balance ?? '0'),
                'end_balance'    => (string) ($r->end_balance ?? '0'),
                'movement'       => (string) ($r->movement ?? '0'),
                'direction'      => $direction,
            ];
        }

        return $out;
    }

    private function safeDeleteExistingTopMovers(string $startDate, string $endDate, string $currencyType, string $scope): void
    {
        $cols = Schema::getColumnListing('top_movers');
        $q = DB::table('top_movers');

        if (in_array('start_date', $cols, true)) $q->whereDate('start_date', $startDate);
        if (in_array('end_date', $cols, true))   $q->whereDate('end_date', $endDate);

        if (in_array('scope', $cols, true)) {
            $q->where('scope', $scope);
        }

        if (in_array('currency_type', $cols, true) && $scope === self::SCOPE_CIF_CURRENCY) {
            $q->where('currency_type', $currencyType);
        }

        $q->delete();
    }

    private function insertTopMovers(array $rows, $now): void
    {
        $cols = Schema::getColumnListing('top_movers');

        $final = [];
        foreach ($rows as $r) {
            $row = [];

            $this->putIfExists($row, $cols, 'start_date', $r['start_date'] ?? null);
            $this->putIfExists($row, $cols, 'end_date', $r['end_date'] ?? null);
            $this->putIfExists($row, $cols, 'currency_type', $r['currency_type'] ?? null);
            $this->putIfExists($row, $cols, 'scope', $r['scope'] ?? self::SCOPE_CIF_CURRENCY);

            $this->putIfExists($row, $cols, 'cif', $r['cif'] ?? null);
            $this->putIfExists($row, $cols, 'customer_name', $r['customer_name'] ?? null);
            $this->putIfExists($row, $cols, 'currency', $r['currency'] ?? null);
            $this->putIfExists($row, $cols, 'branch_code', $r['branch_code'] ?? null);
            $this->putIfExists($row, $cols, 'sub_segment', $r['sub_segment'] ?? null);
            $this->putIfExists($row, $cols, 'cust_ac_no', $r['cust_ac_no'] ?? null);

            $this->putIfExists($row, $cols, 'start_balance', $r['start_balance'] ?? null);
            $this->putIfExists($row, $cols, 'end_balance', $r['end_balance'] ?? null);
            $this->putIfExists($row, $cols, 'movement', $r['movement'] ?? null);
            $this->putIfExists($row, $cols, 'direction', $r['direction'] ?? null);

            if (in_array('created_at', $cols, true)) $row['created_at'] = $now;
            if (in_array('updated_at', $cols, true)) $row['updated_at'] = $now;

            $final[] = $row;
        }

        foreach (array_chunk($final, 1000) as $chunk) {
            DB::table('top_movers')->insert($chunk);
        }
    }

    private function putIfExists(array &$row, array $cols, string $col, $value): void
    {
        if (in_array($col, $cols, true)) {
            $row[$col] = $value;
        }
    }

    /**
     * Left-join each CIF's sub-segment short code (business_seg_short from
     * sub_segment_mappings, resolved via customer_accounts_imports.etibiseg2)
     * onto an already-grouped movers query, adding a `sub_segment` column.
     * A CIF's accounts can carry more than one mis_code, so this collapses
     * to a single deterministic value per CIF (MAX), matching the pattern
     * used elsewhere (CustomerTrendService) of picking one segment per CIF.
     */
    private function joinSubSegment($query, string $alias): void
    {
        if (!Schema::hasTable('customer_accounts_imports') || !Schema::hasTable('sub_segment_mappings')) {
            $query->selectRaw("NULL as sub_segment");
            return;
        }

        $query->leftJoinSub(
            $this->subSegmentByCifSubquery(),
            'ss',
            fn($join) => $join->on('ss.cif', '=', "{$alias}.cif")
        );

        $query->selectRaw('MAX(ss.sub_segment) as sub_segment');
    }

    private function subSegmentByCifSubquery()
    {
        $cifMisSub = DB::table('customer_accounts_imports as cai')
            ->selectRaw('cai.f12_cif as cif, MAX(TRIM(cai.etibiseg2)) as mis_code')
            ->whereNotNull('cai.f12_cif')
            ->whereNotNull('cai.etibiseg2')
            ->whereRaw("TRIM(cai.etibiseg2) <> ''")
            ->groupBy('cai.f12_cif');

        return DB::query()
            ->fromSub($cifMisSub, 'cm')
            ->leftJoin('sub_segment_mappings as sm', 'sm.mis_code', '=', 'cm.mis_code')
            ->selectRaw("cm.cif as cif, COALESCE(sm.business_seg_short, 'UNMAPPED') as sub_segment");
    }

    /**
     * Apply common exclusions:
     * - exclude branch_code P50
     * - exclude cr_gl = 216220001
     *
     * BUT:
     * - if CIF is in the exception list, bypass those exclusions entirely
     */
    private function applyCommonExclusions($query, string $alias = 'cb'): void
    {
        $query->where(function ($main) use ($alias) {
            // Always include these CIFs even if they are P50 / excluded cr_gl
            $main->whereIn("{$alias}.cif", self::INCLUDED_EXCEPTION_CIFS)

                // For all other CIFs, apply the normal exclusion rules
                ->orWhere(function ($normal) use ($alias) {
                    $normal->whereRaw("UPPER(TRIM({$alias}.branch_code)) <> 'P50'");

                    if (Schema::hasColumn('customer_balances', 'cr_gl')) {
                        $normal->where(function ($w) use ($alias) {
                            $w->whereNull("{$alias}.cr_gl")
                              ->orWhere("{$alias}.cr_gl", '<>', self::EXCLUDED_CR_GL);
                        });
                    }
                });
        });
    }
}
