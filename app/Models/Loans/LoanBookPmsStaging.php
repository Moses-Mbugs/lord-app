<?php

namespace App\Models\Loans;

use Illuminate\Database\Eloquent\Model;

class LoanBookPmsStaging extends Model
{
    protected $table = 'loan_book_pms_stagings';

    protected $fillable = [
        'loan_book_run_id',
        'processed_by',
        'row_number',
        'gl_code',
        'related_account',
        'related_customer_id',
        'name',
        'outstanding_amount',
        'raw_payload',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];

    public function run()
    {
        return $this->belongsTo(LoanBookRun::class, 'loan_book_run_id');
    }
}
