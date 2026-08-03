<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductSale;
use App\Support\ActivityLogger;
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
    public function recordFromPosPayment(Payment $payment, Invoice $invoice, array $lineItems): void
    {
        $productLines = collect($lineItems)
            ->filter(fn (array $item): bool => ! empty($item['product_id']))
            ->values();

        if ($productLines->isEmpty()) {
            return;
        }

        $invoiceDiscount = (float) $invoice->discount_amount;
        $invoiceSubtotal = collect($lineItems)->sum('amount');
        $soldAt = $payment->paid_at ?? now();

        foreach ($productLines as $lineItem) {
            $product = Product::query()->lockForUpdate()->find($lineItem['product_id']);

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
                'payment_id' => $payment->id,
                'member_id' => $payment->member_id,
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
                "POS sale {$payment->receipt_number}",
            );
        }

        $this->activityLogger->log('product.sale_recorded', $payment, 'Product sale recorded', [
            'invoice_number' => $invoice->invoice_number,
            'receipt_number' => $payment->receipt_number,
            'product_count' => $productLines->count(),
        ]);
    }
}
