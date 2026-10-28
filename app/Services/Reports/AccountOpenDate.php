<?php

declare(strict_types=1);

namespace App\Services\Reports;

/**
 * customer_accounts_imports.ac_open_date is a varchar holding two formats:
 *
 *  - D-Mon-YY text (e.g. "3-Jul-26") from the original external load, and
 *  - Y-m-d (e.g. "2026-07-03") from finance:sync-customer-accounts, which rewrites every
 *    row it upserts.
 *
 * Every report filtering or grouping on the open date must go through sql() so both parse.
 * Returns a plain SQL fragment with no bindings (NULL when blank or unparseable).
 */
final class AccountOpenDate
{
    /** $alias is the customer_accounts_imports table alias, '' for an unqualified column. */
    public static function sql(string $alias = ''): string
    {
        $col = $alias === '' ? 'ac_open_date' : "{$alias}.ac_open_date";

        return "COALESCE(STR_TO_DATE(TRIM({$col}), '%d-%b-%y'), STR_TO_DATE(TRIM({$col}), '%Y-%m-%d'))";
    }
}
