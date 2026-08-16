<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PosPaymentMode;
use App\Models\Payment;
use App\Models\Product;
use App\Services\PosService;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePosSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Payment::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'member_id' => ['nullable', 'integer', Rule::exists('members', 'id')],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'due_at' => ['nullable', 'date', 'after_or_equal:today'],
            'payment_method' => ['nullable', 'string', Rule::enum(PaymentMethod::class), Rule::requiredIf(fn () => (float) $this->input('amount_paid', 0) > 0)],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            try {
                $lineItems = app(PosService::class)->buildLineItems($this->input('items', []));
            } catch (\InvalidArgumentException $exception) {
                $validator->errors()->add('items', $exception->getMessage());

                return;
            }

            $subtotal = collect($lineItems)->sum('amount');
            $discountAmount = (float) ($this->input('discount_amount') ?? 0);

            if ($discountAmount > $subtotal) {
                $validator->errors()->add('discount_amount', 'Discount cannot exceed the cart subtotal.');
            }

            $totalDue = Money::round(max(0, $subtotal - $discountAmount));
            $amountPaid = Money::round((float) $this->input('amount_paid'));
            $paymentMode = PosPaymentMode::fromAmount($amountPaid, $totalDue);

            if ($amountPaid > $totalDue) {
                $validator->errors()->add('amount_paid', 'Pay amount cannot exceed the billing total.');
            }

            if (in_array($paymentMode, [PosPaymentMode::Partial, PosPaymentMode::Due], true) && blank($this->input('member_id'))) {
                $validator->errors()->add('member_id', 'Select a member when the pay amount is less than the billing total.');
            }

            $productIds = collect($lineItems)->pluck('product_id')->filter()->unique()->values()->all();
            $products = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');

            foreach ($this->input('items', []) as $index => $item) {
                $product = $products->get((int) $item['product_id']);

                if ($product === null) {
                    continue;
                }

                if ((int) $item['quantity'] > $product->stock) {
                    $validator->errors()->add(
                        "items.{$index}.quantity",
                        "Only {$product->stock} units of {$product->name} are available."
                    );
                }
            }
        });
    }
}
