<?php

namespace App\Models\customer_profitability;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UploadBatch extends Model
{
    protected $table = 'upload_batches';

    protected $fillable = [
        'filename',
        'original_name',
        'ytd_label',
    ];

    public function records(): HasMany
    {
        return $this->hasMany(CustomerProfitabilityRecord::class);
    }

    public function ytdRecords(): HasMany
    {
        return $this->hasMany(CustomerProfitabilityRecord::class)
            ->where('record_type', CustomerProfitabilityRecord::TYPE_YTD);
    }

    public function monthlyRecords(): HasMany
    {
        return $this->hasMany(CustomerProfitabilityRecord::class)
            ->where('record_type', CustomerProfitabilityRecord::TYPE_MONTHLY);
    }
}
