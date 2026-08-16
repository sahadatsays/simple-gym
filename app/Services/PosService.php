<?php

namespace App\Services;

use App\Enums\PosPaymentMode;
use App\Enums\ProductStatus;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Support\Carbon;
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
     *     payment_method?: string,
     *     amount_paid?: float|null,
     *     discount_amount?: float,
     *     reference?: string|null,
     *     notes?: string|null,
     *     due_at?: string|null
     * }  $paymentData
     * @return array{invoice: Invoice, payment: ?Payment, total_due: float, amount_paid: float}
     */
    public function checkout(?Member $member, array $cartItems, array $paymentData): array
    {
        $lineItems = $this->buildLineItems($cartItems);
        $subtotal = Money::round(collect($lineItems)->sum('amount'));
        $discountAmount = Money::round((float) ($paymentData['discount_amount'] ?? 0));

        if ($discountAmount > $subtotal) {
            throw new InvalidArgumentException('Discount cannot exceed the cart subtotal.');
        }

        $totalDue = Money::round(max(0, $subtotal - $discountAmount));
        $amountPaid = Money::round((float) ($paymentData['amount_paid'] ?? $totalDue));

        if ($amountPaid > $totalDue) {
            throw new InvalidArgumentException('Pay amount cannot exceed the billing total.');
        }

        $paymentMode = PosPaymentMode::fromAmount($amountPaid, $totalDue);
        $dueAt = $this->resolveDueAt($paymentMode, $paymentData['due_at'] ?? null);

        if (in_array($paymentMode, [PosPaymentMode::Partial, PosPaymentMode::Due], true) && $member === null) {
            throw new InvalidArgumentException('A member is required when the pay amount is less than the billing total.');
        }

        $this->productSaleService->validateStockAvailability($lineItems);

        $result = $this->paymentService->createPosOrder($member, $lineItems, [
            'payment_method' => $paymentData['payment_method'] ?? 'cash',
            'amount_paid' => $amountPaid,
            'discount_amount' => $discountAmount,
            'reference' => $paymentData['reference'] ?? null,
            'notes' => $paymentData['notes'] ?? null,
            'due_at' => $dueAt,
        ]);

        return [
            'invoice' => $result['invoice'],
            'payment' => $result['payment'],
            'total_due' => $totalDue,
            'amount_paid' => $amountPaid,
        ];
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $cartItems
     * @return array<int, array{product_id: int, description: string, amount: float, quantity: int, unit_price: float}>
     */
    public function buildLineItems(array $cartItems): array
    {
        $productIds = collect($cartItems)->pluck('product_id')->unique()->values()->all();
        $products = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');

        return collect($cartItems)
            ->groupBy('product_id')
            ->map(function ($items, $productId) use ($products): array {
                $product = $products->get((int) $productId);

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

    private function resolveDueAt(PosPaymentMode $mode, ?string $dueAt): ?Carbon
    {
        if ($dueAt !== null && $dueAt !== '') {
            return Carbon::parse($dueAt)->endOfDay();
        }

        if (in_array($mode, [PosPaymentMode::Partial, PosPaymentMode::Due], true)) {
            return now()->addDays(7)->endOfDay();
        }

        return null;
    }
}
