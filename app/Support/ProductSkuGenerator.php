<?php

namespace App\Support;

use App\Models\Product;

class ProductSkuGenerator
{
    public function generate(): string
    {
        $nextNumber = (int) Product::query()->withTrashed()->max('id') + 1;

        do {
            $sku = 'PRD-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (Product::query()->withTrashed()->where('sku', $sku)->exists());

        return $sku;
    }
}
