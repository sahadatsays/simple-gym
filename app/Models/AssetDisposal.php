<?php

namespace App\Models;

use App\Enums\AssetDisposalType;
use Database\Factories\AssetDisposalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'asset_id',
    'disposed_at',
    'disposal_type',
    'sale_amount',
    'buyer',
    'reason',
    'notes',
    'created_by',
])]
class AssetDisposal extends Model
{
    /** @use HasFactory<AssetDisposalFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'disposed_at' => 'date',
            'disposal_type' => AssetDisposalType::class,
            'sale_amount' => 'decimal:2',
        ];
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
