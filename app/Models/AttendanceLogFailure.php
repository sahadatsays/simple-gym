<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'sn',
    'user_id',
    'timestamp',
    'punch_status',
    'verify_mode',
    'card_number',
])]
class AttendanceLogFailure extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
        ];
    }
}
