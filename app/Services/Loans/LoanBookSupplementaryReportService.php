<?php

namespace App\Services\Loans;

class LoanBookSupplementaryReportService
{
    protected $parser;
    protected $normalizer;
    protected $validator;

    public function __construct(
        ExcelParserService $parser,
        LoanBookNormalizerService $normalizer,
        ColumnValidatorService $validator
    ) {
        $this->parser = $parser;
        $this->normalizer = $normalizer;
        $this->validator = $validator;
    }

    public function extractPortfolioAccounts($file)
    {
        if (!$file) {
            return $this->emptyResult();
        }

        $config = config('loan_book.portfolio_accounts');

        $parsed = $this->parser->parse(
            $file,
            $config['aliases'],
            $config['required']
        );

        $this->validator->validate(
            $parsed['headers'],
            $config['required'],
            'Portfolio Account Report'
        );

        $selected = [];

        foreach ($parsed['rows'] as $index => $row) {
            $glName = $this->normalizer->text(isset($row['gl_name']) ? $row['gl_name'] : null);

            if (!$this->isPortfolioExposure($glName)) {
                continue;
            }

            $sourceType = $this->getPortfolioSourceType($glName);

            $lcyBalance = $this->normalizer->amount(
                isset($row['lcy_curr_balance']) ? $row['lcy_curr_balance'] : null
            );

            $selected[] = [
                'source_report' => 'PORTFOLIO_ACCOUNT_REPORT',
                'source_type' => $sourceType,
                'source_row_number' => $index + $parsed['header_row_number'] + 1,

                'related_account' => $this->normalizer->account(isset($row['customer_ac_no']) ? $row['customer_ac_no'] : null),
                'related_customer_id' => $this->normalizer->customerId(isset($row['customer_no']) ? $row['customer_no'] : null),
                'name' => $this->normalizer->text(isset($row['description']) ? $row['description'] : null),

                'branch_name' => $this->normalizer->text(isset($row['branch_name']) ? $row['branch_name'] : null),
                'currency' => $this->normalizer->upperText(isset($row['ccy']) ? $row['ccy'] : null),

                'frr' => $this->normalizer->text(isset($row['frr']) ? $row['frr'] : null),
                'orr' => $this->normalizer->text(isset($row['orr']) ? $row['orr'] : null),

                'gl_name' => $glName,

                'status' => $this->normalizer->text(isset($row['status']) ? $row['status'] : null),
                'status_since' => $this->normalizer->date(isset($row['status_since']) ? $row['status_since'] : null),
                'pdo_days' => $this->integerValue(isset($row['pdo_days']) ? $row['pdo_days'] : null),

                'outstanding_amount' => $lcyBalance,
                'lcy_curr_balance' => $lcyBalance,

                'description' => $this->normalizer->text(isset($row['description']) ? $row['description'] : null),
            ];
        }

        return [
            'original_filename' => $this->originalFileName($file),
            'total_rows_read' => $parsed['data_row_count'],
            'total_rows_selected' => count($selected),
            'entries' => $selected,
        ];
    }

    public function extractCreditCards($file)
    {
        if (!$file) {
            return $this->emptyResult();
        }

        $config = config('loan_book.credit_cards');

        $parsed = $this->parser->parse(
            $file,
            $config['aliases'],
            $config['required']
        );

        $this->validator->validate(
            $parsed['headers'],
            $config['required'],
            'Credit Cards Report'
        );

        $selected = [];

        foreach ($parsed['rows'] as $index => $row) {
            $outstandingAmount = $this->normalizer->amount(
                isset($row['outstanding_amount']) ? $row['outstanding_amount'] : null
            );

            if ($outstandingAmount === null || $outstandingAmount >= 0) {
                continue;
            }

            $selected[] = [
                'source_report' => 'CREDIT_CARDS_REPORT',
                'source_type' => 'CREDIT_CARD',
                'source_row_number' => $index + $parsed['header_row_number'] + 1,

                'related_account' => $this->normalizer->account(isset($row['card_account']) ? $row['card_account'] : null),
                'related_customer_id' => $this->normalizer->customerId(isset($row['flexcube_account_cif']) ? $row['flexcube_account_cif'] : null),
                'name' => $this->normalizer->text(isset($row['name']) ? $row['name'] : null),

                'branch_name' => $this->normalizer->text(isset($row['branch_name']) ? $row['branch_name'] : null),

                'currency' => $this->normalizer->upperText(isset($row['contract_currency']) ? $row['contract_currency'] : null),
                'contract_currency' => $this->normalizer->upperText(isset($row['contract_currency']) ? $row['contract_currency'] : null),

                'status' => $this->normalizer->text(isset($row['status']) ? $row['status'] : null),

                'card_account' => $this->normalizer->account(isset($row['card_account']) ? $row['card_account'] : null),

                'outstanding_amount' => $outstandingAmount,

                'amount_arrears' => $this->normalizer->amount(isset($row['amount_arrears']) ? $row['amount_arrears'] : null),
                'days_in_arrears' => $this->integerValue(isset($row['days_in_arrears']) ? $row['days_in_arrears'] : null),

                'interest_rate' => $this->normalizer->decimal(isset($row['rate']) ? $row['rate'] : null, 6),
            ];
        }

        return [
            'original_filename' => $this->originalFileName($file),
            'total_rows_read' => $parsed['data_row_count'],
            'total_rows_selected' => count($selected),
            'entries' => $selected,
        ];
    }

    public function extractLmsLoans($file)
    {
        if (!$file) {
            return $this->emptyResult();
        }

        $config = config('loan_book.lms');

        $parsed = $this->parser->parse(
            $file,
            $config['aliases'],
            $config['required']
        );

        $this->validator->validate(
            $parsed['headers'],
            $config['required'],
            'LMS Loan Portfolio Report'
        );

        $selected = [];

        foreach ($parsed['rows'] as $index => $row) {
            $loanStatus = strtoupper(trim((string) (isset($row['loan_status']) ? $row['loan_status'] : '')));

            if ($loanStatus !== 'ACTIVE') {
                continue;
            }

            $totalOutstanding = $this->normalizer->amount(
                isset($row['total_outstanding']) ? $row['total_outstanding'] : null
            );

            if ($totalOutstanding === null || $totalOutstanding <= 0) {
                continue;
            }

            $selected[] = [
                'source_report'          => 'DIGITAL_LENDING_REPORT',
                'source_type'            => $this->lmsSourceType(isset($row['product_type']) ? $row['product_type'] : null),
                'source_row_number'      => $index + $parsed['header_row_number'] + 1,

                'related_account'        => $this->normalizer->account(isset($row['account_no']) ? $row['account_no'] : null),
                'related_customer_id'    => $this->normalizer->customerId(isset($row['cif_number']) ? $row['cif_number'] : null),
                'name'                   => $this->normalizer->text(isset($row['full_name']) ? $row['full_name'] : null),

                'branch'                 => $this->normalizer->upperText(isset($row['branch']) ? $row['branch'] : null),
                'branch_name'            => $this->normalizer->text(isset($row['branch']) ? $row['branch'] : null),

                'account_status'         => $this->normalizer->text(isset($row['account_status']) ? $row['account_status'] : null),
                'product_type'           => $this->normalizer->text(isset($row['product_type']) ? $row['product_type'] : null),

                'interest_rate'          => $this->normalizer->decimal(isset($row['interest_rate']) ? $row['interest_rate'] : null, 6),
                'tenor'                  => $this->integerValue(isset($row['tenure_months']) ? $row['tenure_months'] : null),

                'limit'                  => $this->normalizer->amount(isset($row['disbursed_amount']) ? $row['disbursed_amount'] : null),

                'value_dt'               => $this->normalizer->date(isset($row['disbursed_at']) ? $row['disbursed_at'] : null),
                'maturity_date'          => $this->normalizer->date(isset($row['next_due_date']) ? $row['next_due_date'] : null),

                'lms_loan_account_no'    => $this->normalizer->account(isset($row['loan_account_no']) ? $row['loan_account_no'] : null),
                'application_ref'        => $this->normalizer->text(isset($row['application_ref']) ? $row['application_ref'] : null),

                'principal_outstanding'  => $this->normalizer->amount(isset($row['principal_outstanding']) ? $row['principal_outstanding'] : null),
                'interest_outstanding'   => $this->normalizer->amount(isset($row['interest_outstanding']) ? $row['interest_outstanding'] : null),
                'penalty_outstanding'    => $this->normalizer->amount(isset($row['penalty_outstanding']) ? $row['penalty_outstanding'] : null),
                'total_repaid'           => $this->normalizer->amount(isset($row['total_repaid']) ? $row['total_repaid'] : null),
                'total_fee_revenue'      => $this->normalizer->amount(isset($row['total_fee_revenue']) ? $row['total_fee_revenue'] : null),
                'dl_fee'                 => $this->normalizer->amount(isset($row['dl_fee']) ? $row['dl_fee'] : null),
                'processing_fee'         => $this->normalizer->amount(isset($row['processing_fee']) ? $row['processing_fee'] : null),
                'insurance_fee'          => $this->normalizer->amount(isset($row['insurance_fee']) ? $row['insurance_fee'] : null),
                'excise_duty'            => $this->normalizer->amount(isset($row['excise_duty']) ? $row['excise_duty'] : null),

                // Negate so buildSupplementaryEntry treats it as a loan (abs → loan_book_outstanding)
                'outstanding_amount'     => -1 * $totalOutstanding,
            ];
        }

        return [
            'original_filename'   => $this->originalFileName($file),
            'total_rows_read'     => $parsed['data_row_count'],
            'total_rows_selected' => count($selected),
            'entries'             => $selected,
        ];
    }

    protected function lmsSourceType($productType)
    {
        $p = strtolower((string) $productType);

        if (strpos($p, 'staff') !== false) {
            return 'LMS_STAFF_LOAN';
        }

        if (strpos($p, 'digital') !== false) {
            return 'LMS_DIGITAL_LOAN';
        }

        return 'LMS_LOAN';
    }

    protected function isPortfolioExposure($glName)
    {
        $glName = strtolower((string) $glName);
        $clean = str_replace(['-', '_', '/', '.'], ' ', $glName);

        if (strpos($clean, 'overdraft') !== false) {
            return true;
        }

        if (strpos($clean, 'writeoff') !== false) {
            return true;
        }

        if (strpos($clean, 'write') !== false && strpos($clean, 'off') !== false) {
            return true;
        }

        return false;
    }

    protected function getPortfolioSourceType($glName)
    {
        $glName = strtolower((string) $glName);
        $clean = str_replace(['-', '_', '/', '.'], ' ', $glName);

        if (
            strpos($clean, 'writeoff') !== false ||
            strpos($clean, 'write off') !== false ||
            (strpos($clean, 'write') !== false && strpos($clean, 'off') !== false)
        ) {
            return 'PORTFOLIO_WRITE_OFF';
        }

        return 'PORTFOLIO_OVERDRAFT';
    }

    protected function integerValue($value)
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $clean = preg_replace('/[^\d\-]/', '', (string) $value);

        if ($clean === '' || $clean === '-') {
            return null;
        }

        return (int) $clean;
    }

    protected function originalFileName($file)
    {
        if (is_object($file) && method_exists($file, 'getClientOriginalName')) {
            return $file->getClientOriginalName();
        }

        if (is_string($file)) {
            return basename($file);
        }

        return null;
    }

    protected function emptyResult()
    {
        return [
            'original_filename' => null,
            'total_rows_read' => 0,
            'total_rows_selected' => 0,
            'entries' => [],
        ];
    }
}
