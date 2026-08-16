<?php

namespace App\Models;

use App\Enums\AssetMaintenanceType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'asset_id',
    'maintained_at',
    'type',
    'cost',
    'service_provider',
    'description',
    'next_maintenance_at',
    'attachment_path',
    'created_by',
])]
class AssetMaintenance extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'maintained_at' => 'date',
            'type' => AssetMaintenanceType::class,
            'cost' => 'decimal:2',
            'next_maintenance_at' => 'date',
        ];
    }

    /**
     * @return Attribute<?string, never>
     */
    protected function attachmentUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->attachment_path
            ? Storage::disk('public')->url($this->attachment_path)
            : null);
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
