<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FinanceDailyMixSummaryService
{
    public const TABLE = 'finance_daily_mix_summaries';

    private const OVERALL_SCOPE = 'OVERALL';
    private const SEGMENT_SCOPE = 'SEGMENT';

    private const OVERALL_SEGMENT_CODE = 'ALL';
    private const OVERALL_SEGMENT_NAME = 'Overall';

    private const SEGMENT_MAP = [
        'CB' => 'Corporate',
        'CM' => 'Commercial',
        'CS' => 'Consumer',
        'OT' => 'Others',
    ];

    private const SEGMENT_COLORS = [
        'CB' => '#005B82',
        'CM' => '#008FC7',
        'CS' => '#10B981',
        'OT' => '#BED600',
    ];

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

    public function latestBalanceDate(): ?string
    {
        $date = DB::table('customer_balances')->max('balance_date');

        return $date ? Carbon::parse((string) $date)->toDateString() : null;
    }

    public function buildForDate(string $balanceDate, bool $force = false): array
    {
        $balanceDate = Carbon::parse($balanceDate)->toDateString();

        if (! $force) {
            $overall = $this->fetchSummary($balanceDate, self::OVERALL_SCOPE, self::OVERALL_SEGMENT_CODE);
            $segments = $this->fetchSegmentSummaries($balanceDate);

            if ($overall !== null && count($segments) === count(self::SEGMENT_MAP)) {
                return [
                    'balance_date' => $balanceDate,
                    'overall' => $overall,
                    'segments' => $segments,
                ];
            }
        }

        $overall = $this->buildOverallSummary($balanceDate);
        $segments = $this->buildSegmentSummaries($balanceDate);

        return [
            'balance_date' => $balanceDate,
            'overall' => $overall,
            'segments' => $segments,
        ];
    }

    public function latestSummary(): ?array
    {
        $row = DB::table(self::TABLE)
            ->where('summary_scope', self::OVERALL_SCOPE)
            ->orderByDesc('balance_date')
            ->first();

        if (! $row) {
            return null;
        }

        $balanceDate = Carbon::parse((string) $row->balance_date)->toDateString();

        return [
            'balance_date' => $balanceDate,
            'overall' => $this->normalizeExistingRow($row),
            'segments' => $this->fetchSegmentSummaries($balanceDate),
        ];
    }

    public function findSummaryOnOrBefore(string $date, string $scope = self::OVERALL_SCOPE, string $segmentCode = self::OVERALL_SEGMENT_CODE): ?array
    {
        $query = DB::table(self::TABLE)
            ->whereDate('balance_date', '<=', Carbon::parse($date)->toDateString())
            ->where('summary_scope', strtoupper(trim($scope)))
            ->orderByDesc('balance_date');

        if (strtoupper(trim($scope)) === self::SEGMENT_SCOPE) {
            $query->where('segment_code', strtoupper(trim($segmentCode)));
        } else {
            $query->where('segment_code', self::OVERALL_SEGMENT_CODE);
        }

        $row = $query->first();

        return $row ? $this->normalizeExistingRow($row) : null;
    }

    private function buildOverallSummary(string $balanceDate): array
    {
        $base = DB::table('customer_balances')
            ->whereDate('balance_date', $balanceDate)
            ->where('lcy_balance', '>', 0);

        $rowCount = (clone $base)->count();

        if ($rowCount === 0) {
            throw new RuntimeException("No positive customer_balances rows found for {$balanceDate}.");
        }

        $currencyAgg = (clone $base)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN UPPER(TRIM(COALESCE(currency, ''))) = 'KES' THEN lcy_balance ELSE 0 END), 0) AS lcy_amount,
                COALESCE(SUM(CASE WHEN UPPER(TRIM(COALESCE(currency, ''))) <> 'KES' THEN lcy_balance ELSE 0 END), 0) AS fcy_amount,
                COALESCE(SUM(lcy_balance), 0) AS total_positive_lcy_balance
            ")
            ->first();

        $depositAgg = (clone $base)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN TRIM(COALESCE(cr_gl, '')) LIKE '211%' THEN lcy_balance ELSE 0 END), 0) AS current_amount,
                COALESCE(SUM(CASE WHEN TRIM(COALESCE(cr_gl, '')) LIKE '212%' THEN lcy_balance ELSE 0 END), 0) AS savings_amount,
                COALESCE(SUM(CASE WHEN TRIM(COALESCE(cr_gl, '')) LIKE '213%' THEN lcy_balance ELSE 0 END), 0) AS term_amount
            ")
            ->first();

        $payload = $this->makePayload(
            $balanceDate,
            self::OVERALL_SCOPE,
            self::OVERALL_SEGMENT_CODE,
            self::OVERALL_SEGMENT_NAME,
            (float) ($currencyAgg->lcy_amount ?? 0),
            (float) ($currencyAgg->fcy_amount ?? 0),
            (float) ($currencyAgg->total_positive_lcy_balance ?? 0),
            (float) ($depositAgg->current_amount ?? 0),
            (float) ($depositAgg->savings_amount ?? 0),
            (float) ($depositAgg->term_amount ?? 0),
            $rowCount,
            '#005B82',
            '#10B981'
        );

        $this->persistPayload($payload);

        return $this->normalizeArrayPayload($payload);
    }

    private function buildSegmentSummaries(string $balanceDate): array
    {
        $classified = DB::query()->fromSub($this->classifiedPositiveBalanceSubquery($balanceDate), 'x');

        $rows = $classified
            ->selectRaw("
                segment_code,
                COALESCE(SUM(CASE WHEN UPPER(TRIM(COALESCE(currency, ''))) = 'KES' THEN lcy_balance ELSE 0 END), 0) AS lcy_amount,
                COALESCE(SUM(CASE WHEN UPPER(TRIM(COALESCE(currency, ''))) <> 'KES' THEN lcy_balance ELSE 0 END), 0) AS fcy_amount,
                COALESCE(SUM(lcy_balance), 0) AS total_positive_lcy_balance,
                COALESCE(SUM(CASE WHEN TRIM(COALESCE(cr_gl, '')) LIKE '211%' THEN lcy_balance ELSE 0 END), 0) AS current_amount,
                COALESCE(SUM(CASE WHEN TRIM(COALESCE(cr_gl, '')) LIKE '212%' THEN lcy_balance ELSE 0 END), 0) AS savings_amount,
                COALESCE(SUM(CASE WHEN TRIM(COALESCE(cr_gl, '')) LIKE '213%' THEN lcy_balance ELSE 0 END), 0) AS term_amount,
                COUNT(*) AS source_row_count
            ")
            ->groupBy('segment_code')
            ->get();

        $rowsByCode = [];
        foreach ($rows as $row) {
            $rowsByCode[strtoupper((string) $row->segment_code)] = $row;
        }

        $summaries = [];
        foreach (self::SEGMENT_MAP as $code => $name) {
            $row = $rowsByCode[$code] ?? null;

            $payload = $this->makePayload(
                $balanceDate,
                self::SEGMENT_SCOPE,
                $code,
                $name,
                (float) ($row->lcy_amount ?? 0),
                (float) ($row->fcy_amount ?? 0),
                (float) ($row->total_positive_lcy_balance ?? 0),
                (float) ($row->current_amount ?? 0),
                (float) ($row->savings_amount ?? 0),
                (float) ($row->term_amount ?? 0),
                (int) ($row->source_row_count ?? 0),
                self::SEGMENT_COLORS[$code],
                '#10B981'
            );

            $this->persistPayload($payload);
            $summaries[$code] = $this->normalizeArrayPayload($payload);
        }

        ksort($summaries);

        return $summaries;
    }

    private function classifiedPositiveBalanceSubquery(string $balanceDate)
    {
        $exceptionSql = "'" . implode("','", array_map('addslashes', self::INCLUDED_EXCEPTION_CIFS)) . "'";

        $cifSegmentSub = DB::table('customer_accounts_imports as cai')
            ->selectRaw("
                cai.f12_cif AS cif,
                MIN(LEFT(TRIM(cai.etibiseg2), 2)) AS segment_code
            ")
            ->whereNotNull('cai.f12_cif')
            ->whereNotNull('cai.etibiseg2')
            ->whereRaw("LEFT(TRIM(cai.etibiseg2), 2) IN ('CB','CM','CS')")
            ->groupBy('cai.f12_cif');

        return DB::table('customer_balances as cb')
            ->leftJoinSub($cifSegmentSub, 'seg', function ($join) {
                $join->on('seg.cif', '=', 'cb.cif');
            })
            ->whereDate('cb.balance_date', $balanceDate)
            ->where('cb.lcy_balance', '>', 0)
            ->whereNotNull('cb.cif')
            ->where(function ($query) use ($exceptionSql) {
                $query->whereRaw("cb.cif IN ({$exceptionSql})")
                    ->orWhere(function ($sub) {
                        $sub->whereRaw("UPPER(TRIM(cb.branch_code)) <> 'P50'")
                            ->where(function ($gl) {
                                $gl->whereNull('cb.cr_gl')
                                    ->orWhere('cb.cr_gl', '<>', self::EXCLUDED_CR_GL);
                            });
                    });
            })
            ->selectRaw("
                cb.cif,
                cb.currency,
                cb.cr_gl,
                cb.lcy_balance,
                COALESCE(seg.segment_code, 'OT') AS segment_code
            ");
    }

    private function makePayload(
        string $balanceDate,
        string $scope,
        string $segmentCode,
        string $segmentName,
        float $lcyAmount,
        float $fcyAmount,
        float $currencyTotal,
        float $currentAmount,
        float $savingsAmount,
        float $termAmount,
        int $rowCount,
        string $lcyColor,
        string $fcyColor
    ): array {
        $lcyAmount = round($lcyAmount, 2);
        $fcyAmount = round($fcyAmount, 2);
        $currencyTotal = round($currencyTotal, 2);

        $currentAmount = round($currentAmount, 2);
        $savingsAmount = round($savingsAmount, 2);
        $termAmount = round($termAmount, 2);

        $depositTotal = round($currentAmount + $savingsAmount + $termAmount, 2);

        $lcyPct = $currencyTotal > 0 ? round(($lcyAmount / $currencyTotal) * 100, 2) : 0.0;
        $fcyPct = $currencyTotal > 0 ? round(($fcyAmount / $currencyTotal) * 100, 2) : 0.0;

        $currentPct = $depositTotal > 0 ? round(($currentAmount / $depositTotal) * 100, 2) : 0.0;
        $savingsPct = $depositTotal > 0 ? round(($savingsAmount / $depositTotal) * 100, 2) : 0.0;
        $termPct = $depositTotal > 0 ? round(($termAmount / $depositTotal) * 100, 2) : 0.0;

        return [
            'balance_date' => $balanceDate,
            'summary_scope' => strtoupper($scope),
            'segment_code' => strtoupper($segmentCode),
            'segment_name' => $segmentName,
            'lcy_amount' => $lcyAmount,
            'fcy_amount' => $fcyAmount,
            'lcy_pct' => $lcyPct,
            'fcy_pct' => $fcyPct,
            'current_amount' => $currentAmount,
            'savings_amount' => $savingsAmount,
            'term_amount' => $termAmount,
            'current_pct' => $currentPct,
            'savings_pct' => $savingsPct,
            'term_pct' => $termPct,
            'total_positive_lcy_balance' => $currencyTotal,
            'source_row_count' => $rowCount,
            'currency_mix_json' => json_encode([
                'labels' => ['LCY', 'FCY'],
                'data' => [$lcyAmount, $fcyAmount],
                'colors' => [$lcyColor, $fcyColor],
            ], JSON_UNESCAPED_UNICODE),
            'deposit_mix_json' => json_encode([
                'labels' => ['Current', 'Savings', 'Term'],
                'data' => [$currentAmount, $savingsAmount, $termAmount],
                'colors' => ['#005B82', '#008FC7', '#10B981'],
            ], JSON_UNESCAPED_UNICODE),
            'last_built_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function persistPayload(array $payload): void
    {
        DB::table(self::TABLE)->updateOrInsert(
            [
                'balance_date' => $payload['balance_date'],
                'summary_scope' => $payload['summary_scope'],
                'segment_code' => $payload['segment_code'],
            ],
            $payload + ['created_at' => now()]
        );
    }

    private function fetchSummary(string $balanceDate, string $scope, string $segmentCode): ?array
    {
        $row = DB::table(self::TABLE)
            ->where('balance_date', $balanceDate)
            ->where('summary_scope', strtoupper($scope))
            ->where('segment_code', strtoupper($segmentCode))
            ->first();

        return $row ? $this->normalizeExistingRow($row) : null;
    }

    private function fetchSegmentSummaries(string $balanceDate): array
    {
        $rows = DB::table(self::TABLE)
            ->where('balance_date', $balanceDate)
            ->where('summary_scope', self::SEGMENT_SCOPE)
            ->whereIn('segment_code', array_keys(self::SEGMENT_MAP))
            ->orderBy('segment_code')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[strtoupper((string) $row->segment_code)] = $this->normalizeExistingRow($row);
        }

        return $out;
    }

    private function normalizeExistingRow(object $row): array
    {
        return [
            'balance_date' => Carbon::parse((string) $row->balance_date)->toDateString(),
            'summary_scope' => strtoupper((string) ($row->summary_scope ?? self::OVERALL_SCOPE)),
            'segment_code' => strtoupper((string) ($row->segment_code ?? self::OVERALL_SEGMENT_CODE)),
            'segment_name' => (string) ($row->segment_name ?? self::OVERALL_SEGMENT_NAME),
            'lcy_amount' => round((float) $row->lcy_amount, 2),
            'fcy_amount' => round((float) $row->fcy_amount, 2),
            'lcy_pct' => round((float) $row->lcy_pct, 2),
            'fcy_pct' => round((float) $row->fcy_pct, 2),
            'current_amount' => round((float) $row->current_amount, 2),
            'savings_amount' => round((float) $row->savings_amount, 2),
            'term_amount' => round((float) $row->term_amount, 2),
            'current_pct' => round((float) $row->current_pct, 2),
            'savings_pct' => round((float) $row->savings_pct, 2),
            'term_pct' => round((float) $row->term_pct, 2),
            'total_positive_lcy_balance' => round((float) $row->total_positive_lcy_balance, 2),
            'source_row_count' => (int) $row->source_row_count,
            'currency_mix' => $this->decodeJson($row->currency_mix_json),
            'deposit_mix' => $this->decodeJson($row->deposit_mix_json),
            'last_built_at' => $row->last_built_at ? Carbon::parse((string) $row->last_built_at)->toDateTimeString() : null,
        ];
    }

    private function normalizeArrayPayload(array $payload): array
    {
        return [
            'balance_date' => $payload['balance_date'],
            'summary_scope' => strtoupper((string) $payload['summary_scope']),
            'segment_code' => strtoupper((string) $payload['segment_code']),
            'segment_name' => (string) $payload['segment_name'],
            'lcy_amount' => round((float) $payload['lcy_amount'], 2),
            'fcy_amount' => round((float) $payload['fcy_amount'], 2),
            'lcy_pct' => round((float) $payload['lcy_pct'], 2),
            'fcy_pct' => round((float) $payload['fcy_pct'], 2),
            'current_amount' => round((float) $payload['current_amount'], 2),
            'savings_amount' => round((float) $payload['savings_amount'], 2),
            'term_amount' => round((float) $payload['term_amount'], 2),
            'current_pct' => round((float) $payload['current_pct'], 2),
            'savings_pct' => round((float) $payload['savings_pct'], 2),
            'term_pct' => round((float) $payload['term_pct'], 2),
            'total_positive_lcy_balance' => round((float) $payload['total_positive_lcy_balance'], 2),
            'source_row_count' => (int) $payload['source_row_count'],
            'currency_mix' => $this->decodeJson($payload['currency_mix_json']),
            'deposit_mix' => $this->decodeJson($payload['deposit_mix_json']),
            'last_built_at' => now()->toDateTimeString(),
        ];
    }

    private function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
