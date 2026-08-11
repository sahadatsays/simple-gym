<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'member_id',
    'serial_number',
    'zkteco_command_id',
    'revoked_at',
])]
class MemberZktecoAccessRemoval extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return BelongsTo<ZktecoCommand, $this>
     */
    public function command(): BelongsTo
    {
        return $this->belongsTo(ZktecoCommand::class, 'zkteco_command_id');
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
