<?php

namespace App\Models\Loans;

use Illuminate\Database\Eloquent\Model;

class LoanBookDetailsStaging extends Model
{
    protected $table = 'loan_book_details_stagings';

    protected $fillable = [
        'loan_book_run_id',
        'processed_by',
        'row_number',
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
        'limit_amount',
        'limit_lcy',
        'group_code',
        'sub_sic_code',
        'business_segment',
        'product_code',
        'latest_status_change',
        'rm_officer',
        'collateral_code',
        'raw_payload',
    ];

    protected $casts = [
        'value_dt' => 'date',
        'maturity_date' => 'date',
        'latest_status_change' => 'date',
        'raw_payload' => 'array',
    ];

    public function run()
    {
        return $this->belongsTo(LoanBookRun::class, 'loan_book_run_id');
    }
}
