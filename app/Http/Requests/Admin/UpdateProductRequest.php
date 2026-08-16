<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Product $product */
        $product = $this->route('product');

        return $this->user()?->can('update', $product) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        return [
            'sku' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products', 'sku')->ignore($product->id)->whereNull('deleted_at'),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'barcode')->ignore($product->id)->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'purchase_price' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'selling_price' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'stock' => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'string', Rule::enum(ProductStatus::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('barcode') && $this->string('barcode')->trim()->isEmpty()) {
            $this->merge(['barcode' => null]);
        }

        if ($this->filled('sku') && $this->string('sku')->trim()->isEmpty()) {
            $this->merge(['sku' => null]);
        }

        if ($this->input('category_id') === '') {
            $this->merge(['category_id' => null]);
        }
    }
}
