<?php

namespace App\Models\Loans;

use Illuminate\Database\Eloquent\Model;

class LoanUtilizationApprovedLimit extends Model
{
    protected $fillable = [
        'product_name',
        'approved_limit',
        'updated_by',
    ];

    protected $casts = [
        'approved_limit' => 'decimal:2',
    ];
}
