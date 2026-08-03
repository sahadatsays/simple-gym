<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\MemberStatus;
use App\Enums\RfidCardStatus;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'member_code',
    'rfid_card',
    'photo_path',
    'name',
    'email',
    'phone',
    'gender',
    'date_of_birth',
    'address',
    'emergency_contact_name',
    'emergency_contact_phone',
    'membership_plan_id',
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
            'date_of_birth' => 'date',
            'status' => MemberStatus::class,
            'gender' => Gender::class,
        ];
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->photo_path
            ? Storage::disk('public')->url($this->photo_path)
            : null);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function initials(): Attribute
    {
        return Attribute::get(function (): string {
            return collect(explode(' ', $this->name))
                ->filter()
                ->take(2)
                ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
                ->implode('');
        });
    }

    /**
     * @return HasMany<RfidCard, $this>
     */
    public function rfidCards(): HasMany
    {
        return $this->hasMany(RfidCard::class);
    }

    /**
     * @return HasOne<RfidCard, $this>
     */
    public function activeRfidCard(): HasOne
    {
        return $this->hasOne(RfidCard::class)->where('status', RfidCardStatus::Active);
    }

    /**
     * @return BelongsTo<MembershipPlan, $this>
     */
    public function membershipPlan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
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
