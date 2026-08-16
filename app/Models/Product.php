<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'sku',
    'barcode',
    'name',
    'category_id',
    'purchase_price',
    'selling_price',
    'stock',
    'minimum_stock',
    'status',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'status' => ProductStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function isLowStock(): bool
    {
        return $this->status === ProductStatus::Active
            && $this->stock <= $this->minimum_stock;
    }

    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }

    /**
     * @return array{
     *     id: int,
     *     sku: string,
     *     barcode: ?string,
     *     name: string,
     *     category: ?string,
     *     selling_price: float,
     *     stock: int,
     *     is_low_stock: bool
     * }
     */
    public function toPosArray(): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'category' => $this->category?->name,
            'selling_price' => (float) $this->selling_price,
            'stock' => $this->stock,
            'is_low_stock' => $this->isLowStock(),
        ];
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Active)
            ->whereColumn('stock', '<=', 'minimum_stock');
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Active);
    }

    /**
     * @return HasMany<ProductSale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(ProductSale::class);
    }
}
