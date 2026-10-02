<?php

declare(strict_types=1);

namespace App\Services\Reports;

/**
 * One CIF → business segment (CB / CM / CS / OT) classification, shared by the daily
 * deposit services and every branch report's "exclude Corporate" filter, so they all
 * agree with the weekly/monthly reports (WeeklySegmentReportService):
 *
 *  1. Per account: sub_segment_mappings.business first (e.g. Local Corporates is
 *     Commercial Banking, CSMS_2100 is Commercial); the etibiseg2 CB/CM/CS prefix
 *     only when the MIS code isn't mapped.
 *  2. Per CIF: Corporate wins over Commercial over Consumer; no vote → OT.
 *  3. WeeklySegmentReportService::CIF_SEGMENT_OVERRIDES on top.
 *
 * All methods return plain SQL with no bindings (override CIFs/codes are hardcoded
 * constants and are inlined), so they drop into raw SQL or whereRaw() safely.
 */
final class CifSegment
{
    /** Derived table SQL with columns (cif, segment_code) — one row per classified CIF. */
    public static function subquerySql(): string
    {
        $overrides = WeeklySegmentReportService::CIF_SEGMENT_OVERRIDES;

        $whens = '';
        foreach ($overrides as $cif => $o) {
            $whens .= " WHEN x.cif = '{$cif}' THEN '{$o['segment_code']}'";
        }
        $overrideCase = $whens === '' ? '' : "CASE{$whens} ELSE ";
        $overrideEnd  = $whens === '' ? '' : ' END';

        // Override CIFs that have no customer_accounts_imports rows at all still need a row.
        $extra = '';
        foreach ($overrides as $cif => $o) {
            $extra .= " UNION ALL SELECT '{$cif}', '{$o['segment_code']}' FROM DUAL"
                . " WHERE NOT EXISTS (SELECT 1 FROM customer_accounts_imports e WHERE e.f12_cif = '{$cif}')";
        }

        return "
            SELECT x.cif,
                {$overrideCase}CASE
                    WHEN SUM(CASE WHEN x.seg = 'CB' THEN 1 ELSE 0 END) > 0 THEN 'CB'
                    WHEN SUM(CASE WHEN x.seg = 'CM' THEN 1 ELSE 0 END) > 0 THEN 'CM'
                    WHEN SUM(CASE WHEN x.seg = 'CS' THEN 1 ELSE 0 END) > 0 THEN 'CS'
                    ELSE 'OT'
                END{$overrideEnd} AS segment_code
            FROM (
                SELECT
                    cai.f12_cif AS cif,
                    CASE
                        WHEN UPPER(TRIM(COALESCE(sm.business, ''))) = 'CORPORATE BANKING'  THEN 'CB'
                        WHEN UPPER(TRIM(COALESCE(sm.business, ''))) = 'COMMERCIAL BANKING' THEN 'CM'
                        WHEN UPPER(TRIM(COALESCE(sm.business, ''))) = 'CONSUMER BANKING'   THEN 'CS'
                        WHEN UPPER(TRIM(cai.etibiseg2)) LIKE 'CB%' THEN 'CB'
                        WHEN UPPER(TRIM(cai.etibiseg2)) LIKE 'CM%' THEN 'CM'
                        WHEN UPPER(TRIM(cai.etibiseg2)) LIKE 'CS%' THEN 'CS'
                        ELSE NULL
                    END AS seg
                FROM customer_accounts_imports cai
                LEFT JOIN sub_segment_mappings sm
                    ON UPPER(TRIM(sm.mis_code)) = UPPER(TRIM(cai.etibiseg2))
                   AND sm.is_active = 1
                WHERE cai.f12_cif IS NOT NULL
            ) x
            GROUP BY x.cif
            {$extra}
        ";
    }

    /** SQL selecting the CIFs classified into any of $codes (e.g. ['CB']). */
    public static function cifsInSql(array $codes): string
    {
        $in = "'" . implode("','", $codes) . "'";

        return "SELECT cs.cif FROM (" . self::subquerySql() . ") cs WHERE cs.segment_code IN ({$in})";
    }

    /** Condition that is true for non-Corporate CIFs; $cifColumn is e.g. 'cb.cif'. */
    public static function notCorporateSql(string $cifColumn): string
    {
        return "({$cifColumn} IS NULL OR {$cifColumn} NOT IN (" . self::cifsInSql(['CB']) . "))";
    }

    /**
     * Condition that is true for non-Corporate loans; $table is the loan_listings
     * table/alias ('' for unqualified columns). Uses the CIF's classification; only when
     * the CIF isn't classified Commercial/Consumer does the loan file's own
     * business_segment decide (as LoanMovementService::segmentExpr() does).
     */
    public static function notCorporateLoanSql(string $table = 'loan_listings'): string
    {
        $p = $table === '' ? '' : "{$table}.";

        return "NOT ("
            . "{$p}cif IN (" . self::cifsInSql(['CB']) . ")"
            . " OR (({$p}cif IS NULL OR {$p}cif NOT IN (" . self::cifsInSql(['CM', 'CS']) . "))"
            . " AND UPPER(TRIM(COALESCE({$p}business_segment, ''))) LIKE '%CORPORATE%')"
            . ")";
    }
}
