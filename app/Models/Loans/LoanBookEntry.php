<?php

namespace App\Models\Loans;

use Illuminate\Database\Eloquent\Model;

class LoanBookEntry extends Model
{
    protected $fillable = [
        'loan_book_run_id',
        'related_account',
        'related_customer_id',
        'name',
        'frr',
        'orr',
        'account_status',
        'value_dt',
        'maturity_date',
        'linecode',
        'branch',
        'product_type',
        'currency',
        'industrycode',
        'status',
        'interest_rate',
        'exch_rate',
        'tenor',
        'limit',
        'limit_lcy',
        'group_code',
        'sub_sic_code',
        'business_segment',
        'product_code',
        'latest_status_change',
        'rm_officer',
        'collateral_code',
        'pms_gl_codes',
        'net_outstanding_amount',
        'loan_book_outstanding',
        'outstanding_amount_lcy',
        'source_report',
        'source_type',
        'branch_name',
        'gl_name',
        'lcy_curr_balance',
        'amount_arrears',
        'days_in_arrears',
        'pdo_days',
        'status_since',
        'card_account',
        'contract_currency',
        'description',
        'source_row_number',
        'outstanding_amount',
        'lms_loan_account_no',
        'application_ref',
        'principal_outstanding',
        'interest_outstanding',
        'penalty_outstanding',
        'total_repaid',
        'total_fee_revenue',
        'dl_fee',
        'processing_fee',
        'insurance_fee',
        'excise_duty',
    ];

    protected $casts = [
        'value_dt' => 'date',
        'maturity_date' => 'date',
        'latest_status_change' => 'date',
        'lcy_curr_balance' => 'decimal:2',
        'amount_arrears' => 'decimal:2',
        'days_in_arrears' => 'integer',
        'pdo_days' => 'integer',
        'status_since' => 'date'
    ];

    public function run()
    {
        return $this->belongsTo(LoanBookRun::class, 'loan_book_run_id');
    }
}
