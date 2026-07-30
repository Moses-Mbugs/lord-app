<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RelationshipManager extends Model
{
    use HasFactory;

    protected $table = 'relationship_managers';

    protected $fillable = [
        'staff_number',
        'rm_code',
        'name',
        'segment',
        'subsegment',
    ];

    public static function rmCodeFromStaffNumber(string|int $staffNumber): string
    {
        return 'KE' . str_pad((string) $staffNumber, 4, '0', STR_PAD_LEFT);
    }
}
