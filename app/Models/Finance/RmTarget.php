<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class RmTarget extends Model
{
    protected $table = 'rm_targets';

    protected $fillable = [
        'rm_code',
        'period_year',
        'deposit_target',
        'loan_target',
        'ntb_target',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'period_year'    => 'integer',
        'deposit_target' => 'float',
        'loan_target'    => 'float',
        'ntb_target'     => 'integer',
    ];
}
