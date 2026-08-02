<?php

namespace App\Models;

use App\Enums\MemberStatus;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'member_code',
    'name',
    'email',
    'phone',
    'joined_at',
    'membership_expires_at',
    'status',
])]
class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'date',
            'membership_expires_at' => 'date',
            'status' => MemberStatus::class,
        ];
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @param  Builder<Member>  $query
     * @return Builder<Member>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', MemberStatus::Active)
            ->where(function (Builder $builder): void {
                $builder->whereNull('membership_expires_at')
                    ->orWhereDate('membership_expires_at', '>=', today());
            });
    }

    /**
     * @param  Builder<Member>  $query
     * @return Builder<Member>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder->where('status', MemberStatus::Expired)
                ->orWhere(function (Builder $nested): void {
                    $nested->whereNotNull('membership_expires_at')
                        ->whereDate('membership_expires_at', '<', today());
                });
        });
    }

    public function isActive(): bool
    {
        if ($this->status !== MemberStatus::Active) {
            return false;
        }

        return $this->membership_expires_at === null
            || $this->membership_expires_at->gte(today());
    }
}
