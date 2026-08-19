<?php

namespace App\Models\Loans;

use Illuminate\Database\Eloquent\Model;

class LoanUtilizationSnapshot extends Model
{
    protected $fillable = [
        'batch_reference',
        'source_filename',
        'as_of_date',
        'status',
        'failure_reason',
        'total_rows',
        'total_exposure_lcy',
        'uploaded_by',
        'processed_at',
    ];

    protected $casts = [
        'as_of_date' => 'date',
        'processed_at' => 'datetime',
        'total_exposure_lcy' => 'decimal:2',
    ];

    public function entries()
    {
        return $this->hasMany(LoanUtilizationEntry::class, 'snapshot_id');
    }
}
