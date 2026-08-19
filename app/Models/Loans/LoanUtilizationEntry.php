<?php

namespace App\Models\Loans;

use Illuminate\Database\Eloquent\Model;

class LoanUtilizationEntry extends Model
{
    protected $fillable = [
        'snapshot_id',
        'account_reference',
        'customer_name',
        'product_name',
        'credit_line_code',
        'gross_outstanding_lcy',
        'dpd',
        'classification_code',
        'risk_rating',
        'classification_label',
        'ifrs9_stage',
        'performance_status',
        'value_date',
        'business',
    ];

    protected $casts = [
        'value_date' => 'date',
        'gross_outstanding_lcy' => 'decimal:2',
        'dpd' => 'integer',
    ];

    public function snapshot()
    {
        return $this->belongsTo(LoanUtilizationSnapshot::class, 'snapshot_id');
    }
}
