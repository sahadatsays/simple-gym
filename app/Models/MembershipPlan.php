<?php

namespace App\Models;

use App\Enums\PlanStatus;
use Database\Factories\MembershipPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'duration_days',
    'admission_fee',
    'membership_fee',
    'description',
    'status',
    'features',
])]
class MembershipPlan extends Model
{
    /** @use HasFactory<MembershipPlanFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
            'admission_fee' => 'decimal:2',
            'membership_fee' => 'decimal:2',
            'status' => PlanStatus::class,
            'features' => 'array',
        ];
    }

    /**
     * @return HasMany<Member, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function isAssignedToMembers(): bool
    {
        return $this->members()->exists();
    }

    /**
     * @param  Builder<MembershipPlan>  $query
     * @return Builder<MembershipPlan>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', PlanStatus::Active);
    }
}
