<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class WeeklyLoanSnapshot extends Model
{
    protected $table = 'weekly_loan_snapshots';

    protected $fillable = [
        'report_date',
        'week_start',
        'mtd_start',
        'segment_code',
        'sub_segment_name',
        'weekly_mv',
        'mtd_mv',
        'total_loans',
    ];

    protected $casts = [
        'report_date' => 'date',
        'week_start'  => 'date',
        'mtd_start'   => 'date',
        'weekly_mv'   => 'float',
        'mtd_mv'      => 'float',
        'total_loans' => 'float',
    ];
}
