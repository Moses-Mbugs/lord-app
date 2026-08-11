<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class TopMover extends Model
{
    protected $table = 'top_movers';

    protected $fillable = [
        'start_date',
        'end_date',
        'currency_type',
        'scope',
        'cif',
        'customer_name',
        'currency',
        'branch_code',
        'sub_segment',
        'cust_ac_no',
        'start_balance',
        'end_balance',
        'movement',
        'direction',
    ];

    protected $casts = [
        'start_date'    => 'date',
        'end_date'      => 'date',
        'start_balance' => 'decimal:2',
        'end_balance'   => 'decimal:2',
        'movement'      => 'decimal:2',
    ];

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeDirection($query, string $direction)
    {
        return $query->where('direction', strtoupper($direction));
    }

    /**
     * Only apply currency_type filter when a non-empty value is provided.
     * Passing null or '' skips the filter (returns all currency types).
     */
    public function scopeCurrencyType($query, ?string $type)
    {
        return filled($type) ? $query->where('currency_type', strtoupper($type)) : $query;
    }

    /**
     * Only apply scope filter when a non-empty value is provided.
     */
    public function scopeForScope($query, ?string $scope)
    {
        return filled($scope) ? $query->where('scope', $scope) : $query;
    }

    public function scopeForBranch($query, ?string $branch)
    {
        return filled($branch) ? $query->where('branch_code', $branch) : $query;
    }

    public function scopeDateRange($query, ?string $from, ?string $to)
    {
        if (filled($from)) {
            $query->where('start_date', $from);  // exact, not >=
        }
        if (filled($to)) {
            $query->where('end_date', $to);      // exact, not <=
        }
        return $query;
    }

    public function scopeMovementRange($query, ?float $min, ?float $max)
    {
        if ($min !== null) {
            $query->where('movement', '>=', $min);
        }
        if ($max !== null) {
            $query->where('movement', '<=', $max);
        }
        return $query;
    }
}
