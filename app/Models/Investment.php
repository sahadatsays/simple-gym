<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Database\Factories\InvestmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'investment_number',
    'invested_at',
    'investment_category_id',
    'amount',
    'payment_method',
    'description',
    'attachment_path',
    'created_by',
])]
class Investment extends Model
{
    /** @use HasFactory<InvestmentFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'invested_at' => 'date',
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
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
     * @return BelongsTo<InvestmentCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(InvestmentCategory::class, 'investment_category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
