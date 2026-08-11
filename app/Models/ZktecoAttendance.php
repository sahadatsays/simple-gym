<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'connection',
    'uid',
    'user_id',
    'recorded_at',
    'verify_mode',
    'punch_state',
])]
class ZktecoAttendance extends Model
{
    protected $table = 'zkteco_attendance';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }
}
