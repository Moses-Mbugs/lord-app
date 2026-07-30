<?php

namespace App\Models\customer_profitability;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProfitabilityRecord extends Model
{
    protected $table = 'customer_profitability_records';

    const TYPE_YTD     = 'ytd';
    const TYPE_MONTHLY = 'monthly';

    protected $fillable = [
        'upload_batch_id',
        'record_type',
        'cif',
        'name',
        'segment',
        'rm',
        'month',
        'interest_from_loans',
        'interest_from_ods',
        'interest_from_trade',
        'total_interest_income',
        'interest_paid',
        'net_ftp_interest',
        'net_interest_income',
        'payments',
        'receivables',
        'liquidity',
        'cash_management',
        'fees_and_commissions',
        'trade_fees',
        'acquiring_expense',
        'total_fees',
        'fx_income',
        'other_income',
        'total_revenue',
        'ftp_income',
        'ftp_expense',
        'casa_lcy',
        'casa_fcy',
        'term_deposits_lcy',
        'term_deposits_fcy',
        'total_deposits',
        'loans_lcy',
        'loans_fcy',
        'od_lcy',
        'od_fcy',
        'gross_loans',
    ];

    protected $casts = [
        'interest_from_loans'   => 'float',
        'interest_from_ods'     => 'float',
        'interest_from_trade'   => 'float',
        'total_interest_income' => 'float',
        'interest_paid'         => 'float',
        'net_ftp_interest'      => 'float',
        'net_interest_income'   => 'float',
        'payments'              => 'float',
        'receivables'           => 'float',
        'liquidity'             => 'float',
        'cash_management'       => 'float',
        'fees_and_commissions'  => 'float',
        'trade_fees'            => 'float',
        'acquiring_expense'     => 'float',
        'total_fees'            => 'float',
        'fx_income'             => 'float',
        'other_income'          => 'float',
        'total_revenue'         => 'float',
        'ftp_income'            => 'float',
        'ftp_expense'           => 'float',
        'casa_lcy'              => 'float',
        'casa_fcy'              => 'float',
        'term_deposits_lcy'     => 'float',
        'term_deposits_fcy'     => 'float',
        'total_deposits'        => 'float',
        'loans_lcy'             => 'float',
        'loans_fcy'             => 'float',
        'od_lcy'                => 'float',
        'od_fcy'                => 'float',
        'gross_loans'           => 'float',
    ];

    public function scopeYtd(Builder $query): Builder
    {
        return $query->where('record_type', self::TYPE_YTD);
    }

    public function scopeMonthly(Builder $query): Builder
    {
        return $query->where('record_type', self::TYPE_MONTHLY);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(UploadBatch::class, 'upload_batch_id');
    }
}
