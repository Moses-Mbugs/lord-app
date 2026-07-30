<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class GroupMover extends Model
{
    protected $table = 'group_movers';

    protected $fillable = [
        'group_type',
        'group_key',
        'group_name',
        'start_date',
        'end_date',
        'start_balance',
        'end_balance',
        'movement',
        'scope',
        'direction',
        'rank',
    ];

    protected $casts = [
        'start_date'    => 'date',
        'end_date'      => 'date',
        'start_balance' => 'decimal:2',
        'end_balance'   => 'decimal:2',
        'movement'      => 'decimal:2',
        'rank'          => 'integer',
    ];

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeDirection($query, string $direction)
    {
        return $query->where('direction', strtoupper($direction));
    }

    public function scopeGroupType($query, ?string $groupType)
    {
        return $groupType ? $query->where('group_type', $groupType) : $query;
    }

    public function scopeForScope($query, ?string $scope)
    {
        return $scope ? $query->where('scope', $scope) : $query;
    }

    public function scopeDateRange($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->where('start_date', '>=', $from);
        }
        if ($to) {
            $query->where('end_date', '<=', $to);
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
