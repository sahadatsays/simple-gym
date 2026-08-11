<?php

namespace App\Models;

use App\Enums\ZktecoDeviceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'serial_number',
    'protocol_generation',
    'status',
    'capabilities',
    'stamps',
    'last_seen_at',
])]
class ZktecoDevice extends Model
{
    protected $table = 'zkteco_devices';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'stamps' => 'array',
            'last_seen_at' => 'datetime',
            'status' => ZktecoDeviceStatus::class,
        ];
    }

    /**
     * @return HasMany<ZktecoCommand, $this>
     */
    public function commands(): HasMany
    {
        return $this->hasMany(
            ZktecoCommand::class,
            'serial_number',
            'serial_number',
        );
    }

    public function isApproved(): bool
    {
        return $this->status === ZktecoDeviceStatus::Active;
    }
}
