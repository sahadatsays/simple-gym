<?php

namespace App\Services;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Models\Product;
use App\Support\ActivityLogger;
use App\Support\ProductSkuGenerator;
use InvalidArgumentException;

class ProductService extends BaseService
{
    public function __construct(
        private ProductRepositoryInterface $products,
        private ProductSkuGenerator $skuGenerator,
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        return $this->transaction(function () use ($data): Product {
            if (blank($data['sku'] ?? null)) {
                $data['sku'] = $this->skuGenerator->generate();
            }

            $product = $this->products->create($data);

            $this->activityLogger->log('product.created', $product, 'Product created', [
                'sku' => $product->sku,
                'name' => $product->name,
            ]);

            return $product;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        return $this->transaction(function () use ($product, $data): Product {
            if (blank($data['sku'] ?? null)) {
                $data['sku'] = $product->sku ?: $this->skuGenerator->generate();
            }

            $updatedProduct = $this->products->update($product, $data);

            $this->activityLogger->log('product.updated', $updatedProduct, 'Product updated', [
                'sku' => $updatedProduct->sku,
            ]);

            return $updatedProduct;
        });
    }

    public function delete(Product $product): void
    {
        $this->transaction(function () use ($product): void {
            $this->activityLogger->log('product.deleted', $product, 'Product deleted', [
                'sku' => $product->sku,
                'name' => $product->name,
            ]);

            $this->products->delete($product);
        });
    }

    public function adjustStock(Product $product, int $adjustment, ?string $notes = null): Product
    {
        return $this->transaction(function () use ($product, $adjustment, $notes): Product {
            $product->refresh();

            $newStock = $product->stock + $adjustment;

            if ($newStock < 0) {
                throw new InvalidArgumentException('Stock cannot be reduced below zero.');
            }

            $updatedProduct = $this->products->update($product, [
                'stock' => $newStock,
            ]);

            $this->activityLogger->log('product.stock_adjusted', $updatedProduct, 'Product stock adjusted', [
                'sku' => $updatedProduct->sku,
                'adjustment' => $adjustment,
                'previous_stock' => $product->stock,
                'new_stock' => $newStock,
                'notes' => $notes,
            ]);

            return $updatedProduct;
        });
    }
}
