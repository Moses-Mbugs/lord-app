<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class MonthlyReportSnapshot extends Model
{
    public const TYPE_LOANS_DEPOSITS = 'loans_deposits';
    public const TYPE_BRANCHES       = 'branches';

    protected $table = 'monthly_report_snapshots';

    protected $fillable = [
        'report_type',
        'month',
        'deposits_as_at',
        'loans_start',
        'loans_as_at',
        'payload',
    ];

    protected $casts = [
        'deposits_as_at' => 'date',
        'loans_start'    => 'date',
        'loans_as_at'    => 'date',
        'payload'        => 'array',
    ];
}
