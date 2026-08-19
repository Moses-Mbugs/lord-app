<?php

namespace App\Models\Loans;

use Illuminate\Database\Eloquent\Model;

class LoanUtilizationProductOverride extends Model
{
    protected $fillable = [
        'credit_line_code',
        'product_name',
        'updated_by',
    ];
}
