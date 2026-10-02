<?php

namespace App\Models;

use App\Enums\LockerStatus;
use Database\Factories\LockerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'locker_number',
    'location',
    'category',
    'monthly_fee',
    'status',
    'notes',
    'created_by',
])]
class Locker extends Model
{
    /** @use HasFactory<LockerFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monthly_fee' => 'decimal:2',
            'status' => LockerStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canBeReserved(): bool
    {
        return $this->status->canBeReserved();
    }
}
