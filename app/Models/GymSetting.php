<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'email',
    'phone',
    'address',
    'logo_path',
    'timezone',
    'currency',
    'opening_time',
    'closing_time',
    'is_open',
    'meta',
])]
class GymSetting extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_time' => 'datetime:H:i:s',
            'closing_time' => 'datetime:H:i:s',
            'is_open' => 'boolean',
            'meta' => 'array',
        ];
    }
}
