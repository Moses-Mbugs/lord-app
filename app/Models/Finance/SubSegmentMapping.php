<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubSegmentMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'mis_class',
        'business_segment_name',
        'mis_code',
        'code_desc',
        'business_seg_short',
        'business',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}