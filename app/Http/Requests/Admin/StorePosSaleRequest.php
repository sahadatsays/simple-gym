<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Models\Product;
use App\Services\PosService;
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
            'payment_method' => ['required', 'string', Rule::enum(PaymentMethod::class)],
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
