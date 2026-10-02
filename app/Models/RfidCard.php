<?php

namespace App\Models;

use App\Enums\RfidCardStatus;
use Database\Factories\RfidCardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

#[Fillable([
    'card_number',
    'card_fee',
    'deposit_amount',
    'status',
    'member_id',
    'assigned_at',
    'created_by',
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
            'card_fee' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<RfidCardAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(RfidCardAssignment::class)->latest('issue_date')->latest('id');
    }

    /**
     * @return HasOne<RfidCardAssignment, $this>
     */
    public function openAssignment(): HasOne
    {
        return $this->hasOne(RfidCardAssignment::class)->whereNull('return_date');
    }

    public function isAssignable(): bool
    {
        return $this->status->isAssignable();
    }

    public function isActive(): bool
    {
        return $this->status === RfidCardStatus::Assigned;
    }

    public function isDisabled(): bool
    {
        return $this->status === RfidCardStatus::Blocked;
    }

    public function canBeEnabled(): bool
    {
        if (! $this->isDisabled() || $this->member_id === null) {
            return false;
        }

        $member = $this->relationLoaded('member') ? $this->member : $this->member()->first();

        if ($member === null || ! $member->isActive()) {
            return false;
        }

        return ! $member->rfidCards()->where('status', RfidCardStatus::Assigned)->exists();
    }

    /**
     * @param  Builder<RfidCard>  $query
     * @return Builder<RfidCard>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', RfidCardStatus::Assigned);
    }

    /**
     * @param  iterable<int|string|null>  $pims
     * @return Collection<string, Member>
     */
    public static function membersKeyedByPim(iterable $pims): Collection
    {
        $ids = collect($pims)
            ->filter()
            ->map(fn ($pim) => (int) $pim)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return static::query()
            ->with('member:id,name,member_code')
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(fn (self $card) => [(string) $card->id => $card->member])
            ->filter();
    }
}
