<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RmMover extends Model
{
    use HasFactory;

    protected $table = 'rm_movers';

    protected $fillable = [
        'start_date',
        'end_date',
        'rm_code',
        'start_balance',
        'end_balance',
        'movement',
        'cif_count',
    ];

    protected $casts = [
        'start_date'    => 'date',
        'end_date'      => 'date',
        'start_balance' => 'decimal:2',
        'end_balance'   => 'decimal:2',
        'movement'      => 'decimal:2',
        'cif_count'     => 'integer',
    ];
}
