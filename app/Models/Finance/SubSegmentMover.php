<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubSegmentMover extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_date',
        'end_date',
        'business',
        'business_segment_name',
        'business_seg_short',
        'mis_code',
        'code_desc',
        'start_balance',
        'end_balance',
        'movement',
        'cif_count',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_balance' => 'decimal:2',
        'end_balance' => 'decimal:2',
        'movement' => 'decimal:2',
        'cif_count' => 'integer',
    ];
}