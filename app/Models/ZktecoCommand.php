<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'serial_number',
    'command',
    'status',
    'return_code',
    'sent_at',
    'acknowledged_at',
])]
class ZktecoCommand extends Model
{
    protected $table = 'zkteco_commands';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'return_code' => 'integer',
            'sent_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ZktecoDevice, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(
            ZktecoDevice::class,
            'serial_number',
            'serial_number',
        );
    }
}
