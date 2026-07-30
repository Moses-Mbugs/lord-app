<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class LoanListing extends Model
{
    protected $table = 'loan_listings';

    protected $fillable = [
        'as_at_date',
        'related_account',
        'cif',
        'name',
        'branch',
        'currency',
        'currency_type',
        'business_segment',
        'loan_status',
        'status_bucket',
        'loan_book_outstanding',
        'outstanding_amount_lcy',
        'product_code',
        'pms_gl_codes',
        'linecode',
        'raw',
    ];

    protected $casts = [
        'as_at_date'             => 'date',
        'loan_book_outstanding'  => 'decimal:2',
        'outstanding_amount_lcy' => 'decimal:2',
        'raw'                    => 'array',
    ];
}
