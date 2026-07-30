<?php

namespace App\Models\Loans;

use Illuminate\Database\Eloquent\Model;

class LoanBookException extends Model
{
    protected $fillable = [
        'loan_book_run_id',
        'exception_type',
        'source',
        'related_account',
        'related_customer_id',
        'name',
        'amount',
        'remarks',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function run()
    {
        return $this->belongsTo(LoanBookRun::class, 'loan_book_run_id');
    }
}
