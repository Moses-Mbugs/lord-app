<?php

declare(strict_types=1);

namespace App\Services\Reports;

/**
 * Staff accounts and staff loans are excluded from every deposit and loan report
 * (daily, weekly, monthly, branch, RM) — same rules RmMoversService / RmLoanMoversService
 * introduced:
 *
 *  - Deposits: the account's customer_accounts_imports.account_class is KECATF.
 *  - Loans:    loan_listings.linecode contains "STAFF" (case-insensitive).
 *
 * Both return plain SQL fragments with no bindings, so they can be dropped into raw
 * SQL strings or passed to whereRaw() without disturbing the caller's bindings.
 */
final class StaffExclusion
{
    public const STAFF_ACCOUNT_CLASS = 'KECATF';

    /** NOT EXISTS clause excluding staff deposit accounts; $alias is the customer_balances alias. */
    public static function depositSql(string $alias = 'cb'): string
    {
        return "NOT EXISTS (SELECT 1 FROM customer_accounts_imports cai_staff"
            . " WHERE cai_staff.cust_ac_no = {$alias}.cust_ac_no"
            . " AND UPPER(TRIM(COALESCE(cai_staff.account_class, ''))) = '" . self::STAFF_ACCOUNT_CLASS . "')";
    }

    /**
     * Condition excluding staff accounts when querying customer_accounts_imports directly
     * (NTB, account counts); $alias is its table name/alias, '' for an unqualified column.
     */
    public static function accountSql(string $alias = ''): string
    {
        $col = $alias === '' ? 'account_class' : "{$alias}.account_class";

        return "UPPER(TRIM(COALESCE({$col}, ''))) <> '" . self::STAFF_ACCOUNT_CLASS . "'";
    }

    /** Condition excluding staff loans; $table is the loan_listings table name or alias. */
    public static function loanSql(string $table = 'loan_listings'): string
    {
        $col = $table === '' ? 'linecode' : "{$table}.linecode";

        return "UPPER(COALESCE({$col}, '')) NOT LIKE '%STAFF%'";
    }
}
