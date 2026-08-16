<?php

namespace App\Models;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'asset_code',
    'name',
    'asset_category_id',
    'purchased_at',
    'purchase_price',
    'current_value',
    'supplier',
    'location',
    'condition',
    'status',
    'warranty_expires_at',
    'notes',
    'created_by',
])]
class Asset extends Model
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchased_at' => 'date',
            'purchase_price' => 'decimal:2',
            'current_value' => 'decimal:2',
            'condition' => AssetCondition::class,
            'status' => AssetStatus::class,
            'warranty_expires_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<AssetCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<AssetMaintenance, $this>
     */
    public function maintenances(): HasMany
    {
        return $this->hasMany(AssetMaintenance::class);
    }

    /**
     * @return HasOne<AssetDisposal, $this>
     */
    public function disposal(): HasOne
    {
        return $this->hasOne(AssetDisposal::class);
    }

    public function isDisposed(): bool
    {
        return $this->status === AssetStatus::Disposed;
    }

    public function isEligibleForMaintenance(): bool
    {
        return $this->status !== null && $this->status->isOperational();
    }

    public function isEligibleForDisposal(): bool
    {
        if ($this->status === null || ! $this->status->isOperational()) {
            return false;
        }

        if ($this->relationLoaded('disposal')) {
            return $this->disposal === null;
        }

        return ! $this->disposal()->exists();
    }

    public function isDeletable(): bool
    {
        if ($this->status === null || ! $this->status->isOperational()) {
            return false;
        }

        if ($this->relationLoaded('disposal')) {
            return $this->disposal === null;
        }

        return ! $this->disposal()->exists();
    }

    /**
     * @param  Builder<Asset>  $query
     * @return Builder<Asset>
     */
    public function scopeDisposable(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [
                AssetStatus::Active,
                AssetStatus::UnderMaintenance,
                AssetStatus::Damaged,
            ])
            ->whereDoesntHave('disposal');
    }

    /**
     * @param  Builder<Asset>  $query
     * @return Builder<Asset>
     */
    public function scopeMaintainable(Builder $query): Builder
    {
        return $query->whereIn('status', [
            AssetStatus::Active,
            AssetStatus::UnderMaintenance,
            AssetStatus::Damaged,
        ]);
    }

    /**
     * @param  Builder<Asset>  $query
     * @return Builder<Asset>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', AssetStatus::Active);
    }
}
