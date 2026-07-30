<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class WeeklySegmentSnapshot extends Model
{
    protected $table = 'weekly_segment_snapshots';

    protected $fillable = [
        'report_date',
        'week_start',
        'mtd_start',
        'ytd_start',
        'segment_code',
        'sub_segment_name',
        'bank_weekly_mv',
        'bank_mtd_mv',
        'bank_ytd_mv',
        'bank_total_deposits',
        'lcy_weekly_mv',
        'lcy_mtd_mv',
        'lcy_ytd_mv',
        'lcy_total_deposits',
        'fcy_weekly_mv',
        'fcy_mtd_mv',
        'fcy_ytd_mv',
        'fcy_total_deposits',
    ];

    protected $casts = [
        'report_date'         => 'date',
        'week_start'          => 'date',
        'mtd_start'           => 'date',
        'ytd_start'           => 'date',
        'bank_weekly_mv'      => 'float',
        'bank_mtd_mv'         => 'float',
        'bank_ytd_mv'         => 'float',
        'bank_total_deposits' => 'float',
        'lcy_weekly_mv'       => 'float',
        'lcy_mtd_mv'          => 'float',
        'lcy_ytd_mv'          => 'float',
        'lcy_total_deposits'  => 'float',
        'fcy_weekly_mv'       => 'float',
        'fcy_mtd_mv'          => 'float',
        'fcy_ytd_mv'          => 'float',
        'fcy_total_deposits'  => 'float',
    ];
}
