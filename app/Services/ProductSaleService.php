<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductSale;
use App\Support\ActivityLogger;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ProductSaleService extends BaseService
{
    public function __construct(
        private ProductService $productService,
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<int, array{
     *     product_id?: int|null,
     *     description: string,
     *     amount: float,
     *     quantity?: int,
     *     unit_price?: float
     * }>  $lineItems
     */
    public function validateStockAvailability(array $lineItems): void
    {
        $productLines = $this->groupProductLines($lineItems);
        $products = $this->loadProducts($productLines->pluck('product_id')->all());

        foreach ($productLines as $line) {
            $product = $products->get($line['product_id']);

            if ($product === null) {
                throw new InvalidArgumentException('One or more products in this sale no longer exist.');
            }

            if ($product->stock < $line['quantity']) {
                throw new InvalidArgumentException("Insufficient stock for {$product->name}. Available: {$product->stock}.");
            }
        }
    }

    /**
     * @param  array<int, array{
     *     product_id?: int|null,
     *     description: string,
     *     amount: float,
     *     quantity?: int,
     *     unit_price?: float
     * }>  $lineItems
     */
    public function recordFromPosOrder(Invoice $invoice, array $lineItems, ?Payment $payment = null): void
    {
        if ($invoice->productSales()->exists()) {
            return;
        }

        $this->recordSales($invoice, $lineItems, $payment, $payment?->receipt_number ?? $invoice->invoice_number);
    }

    /**
     * @param  array<int, array{
     *     product_id?: int|null,
     *     description: string,
     *     amount: float,
     *     quantity?: int,
     *     unit_price?: float
     * }>  $lineItems
     */
    public function recordFromPosPayment(Payment $payment, Invoice $invoice, array $lineItems): void
    {
        $this->recordFromPosOrder($invoice, $lineItems, $payment);
    }

    /**
     * @param  array<int, array{
     *     product_id?: int|null,
     *     description: string,
     *     amount: float,
     *     quantity?: int,
     *     unit_price?: float
     * }>  $lineItems
     */
    private function recordSales(Invoice $invoice, array $lineItems, ?Payment $payment, string $reference): void
    {
        $productLines = collect($lineItems)
            ->filter(fn (array $item): bool => ! empty($item['product_id']))
            ->values();

        if ($productLines->isEmpty()) {
            return;
        }

        $this->validateStockAvailability($lineItems);

        $products = $this->loadProducts(
            $productLines->pluck('product_id')->all(),
            lockForUpdate: true,
        );

        $invoiceDiscount = (float) $invoice->discount_amount;
        $invoiceSubtotal = collect($lineItems)->sum('amount');
        $soldAt = $payment?->paid_at ?? now();

        foreach ($productLines as $lineItem) {
            $product = $products->get((int) $lineItem['product_id']);

            if ($product === null) {
                throw new InvalidArgumentException('One or more products in this sale no longer exist.');
            }

            $quantity = max(1, (int) ($lineItem['quantity'] ?? 1));
            $lineAmount = (float) $lineItem['amount'];

            if ($product->stock < $quantity) {
                throw new InvalidArgumentException("Insufficient stock for {$product->name}. Available: {$product->stock}.");
            }

            $lineDiscount = $invoiceSubtotal > 0
                ? round($invoiceDiscount * ($lineAmount / $invoiceSubtotal), 2)
                : 0.0;
            $lineTotal = max(0, $lineAmount - $lineDiscount);
            $unitPrice = (float) ($lineItem['unit_price'] ?? ($quantity > 0 ? $lineAmount / $quantity : $lineAmount));

            ProductSale::query()->create([
                'product_id' => $product->id,
                'invoice_id' => $invoice->id,
                'payment_id' => $payment?->id,
                'member_id' => $invoice->member_id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'unit_cost' => (float) $product->purchase_price,
                'line_discount' => $lineDiscount,
                'line_total' => $lineTotal,
                'sold_at' => $soldAt,
            ]);

            $this->productService->adjustStock(
                $product,
                -$quantity,
                "POS order {$reference}",
            );
        }

        $this->activityLogger->log('product.sale_recorded', $invoice, 'Product sale recorded', [
            'invoice_number' => $invoice->invoice_number,
            'receipt_number' => $payment?->receipt_number,
            'product_count' => $productLines->count(),
        ]);
    }

    /**
     * @param  array<int, array{
     *     product_id?: int|null,
     *     description: string,
     *     amount: float,
     *     quantity?: int,
     *     unit_price?: float
     * }>  $lineItems
     * @return Collection<int, array{product_id: int, quantity: int}>
     */
    private function groupProductLines(array $lineItems): Collection
    {
        return collect($lineItems)
            ->filter(fn (array $item): bool => ! empty($item['product_id']))
            ->groupBy('product_id')
            ->map(fn ($items) => [
                'product_id' => (int) $items->first()['product_id'],
                'quantity' => (int) $items->sum(fn (array $item): int => (int) ($item['quantity'] ?? 1)),
            ])
            ->values();
    }

    /**
     * @param  array<int, int>  $productIds
     * @return Collection<int, Product>
     */
    private function loadProducts(array $productIds, bool $lockForUpdate = false): Collection
    {
        $query = Product::query()->whereIn('id', $productIds);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->get()->keyBy('id');
    }
}
