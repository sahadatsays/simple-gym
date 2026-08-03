<?php

namespace App\Services;

use App\Enums\PaymentType;
use App\Enums\ProductStatus;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Product;
use App\Support\Money;
use InvalidArgumentException;

class PosService extends BaseService
{
    public function __construct(
        private PaymentService $paymentService,
        private ProductSaleService $productSaleService,
    ) {}

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $cartItems
     * @param  array{
     *     payment_method: string,
     *     discount_amount?: float,
     *     reference?: string|null,
     *     notes?: string|null
     * }  $paymentData
     */
    public function checkout(?Member $member, array $cartItems, array $paymentData): Payment
    {
        $lineItems = $this->buildLineItems($cartItems);
        $subtotal = Money::round(collect($lineItems)->sum('amount'));
        $discountAmount = Money::round((float) ($paymentData['discount_amount'] ?? 0));

        if ($discountAmount > $subtotal) {
            throw new InvalidArgumentException('Discount cannot exceed the cart subtotal.');
        }

        $totalDue = Money::round(max(0, $subtotal - $discountAmount));

        $this->productSaleService->validateStockAvailability($lineItems);

        return $this->paymentService->receivePosSale(
            $member,
            $lineItems,
            [
                'type' => PaymentType::PosSale,
                'amount_paid' => $totalDue,
                'payment_method' => $paymentData['payment_method'],
                'discount_amount' => $discountAmount,
                'reference' => $paymentData['reference'] ?? null,
                'notes' => $paymentData['notes'] ?? null,
            ],
        );
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $cartItems
     * @return array<int, array{product_id: int, description: string, amount: float, quantity: int, unit_price: float}>
     */
    public function buildLineItems(array $cartItems): array
    {
        return collect($cartItems)
            ->groupBy('product_id')
            ->map(function ($items, $productId): array {
                $product = Product::query()->find((int) $productId);

                if ($product === null) {
                    throw new InvalidArgumentException('One or more products in the cart no longer exist.');
                }

                if ($product->status !== ProductStatus::Active) {
                    throw new InvalidArgumentException("{$product->name} is not available for sale.");
                }

                $quantity = (int) $items->sum('quantity');
                $unitPrice = (float) $product->selling_price;

                return [
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'amount' => round($unitPrice * $quantity, 2),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ];
            })
            ->values()
            ->all();
    }
}
