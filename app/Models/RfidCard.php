<?php

namespace App\Models;

use App\Enums\RfidCardStatus;
use Database\Factories\RfidCardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'card_number',
    'status',
    'member_id',
    'assigned_at',
])]
class RfidCard extends Model
{
    /** @use HasFactory<RfidCardFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RfidCardStatus::class,
            'assigned_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function isAssignable(): bool
    {
        return $this->status === RfidCardStatus::Unassigned;
    }

    public function isActive(): bool
    {
        return $this->status === RfidCardStatus::Active;
    }

    /**
     * @param  Builder<RfidCard>  $query
     * @return Builder<RfidCard>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', RfidCardStatus::Active);
    }
}
