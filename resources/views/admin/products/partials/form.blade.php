@props(['product'])

<div class="row">
    <div class="col-lg-8">
        <div class="row">
            <div class="col-md-6">
                <x-forms.input
                    label="SKU"
                    name="sku"
                    placeholder="SKU-0001"
                    :value="$product?->sku"
                    required
                />
            </div>
            <div class="col-md-6">
                <x-forms.input
                    label="Barcode"
                    name="barcode"
                    placeholder="Scan or enter barcode"
                    :value="$product?->barcode"
                    help="Optional. Used for POS barcode scanning."
                />
            </div>
        </div>

        <x-forms.input
            label="Product name"
            name="name"
            placeholder="Whey Protein 1kg"
            :value="$product?->name"
            required
        />

        <label for="category" class="form-label">Category</label>
        <input
            type="text"
            name="category"
            id="category"
            list="product-categories"
            value="{{ old('category', $product?->category) }}"
            placeholder="Supplements"
            @class(['form-control mb-3', 'is-invalid' => $errors->has('category')])
        >
        <datalist id="product-categories">
            @foreach (array_unique(array_merge(config('gym.product_categories', []), $categories ?? [])) as $category)
                <option value="{{ $category }}"></option>
            @endforeach
        </datalist>
        @error('category')
            <div class="invalid-feedback d-block mb-3">{{ $message }}</div>
        @enderror

        <div class="row">
            <div class="col-md-6">
                <x-forms.money-input
                    label="Purchase price"
                    name="purchase_price"
                    :value="$product?->purchase_price"
                    required
                />
            </div>
            <div class="col-md-6">
                <x-forms.money-input
                    label="Selling price"
                    name="selling_price"
                    :value="$product?->selling_price"
                    required
                />
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <x-forms.input
                    label="Stock"
                    name="stock"
                    type="number"
                    min="0"
                    :value="$product?->stock ?? 0"
                    required
                />
            </div>
            <div class="col-md-6">
                <x-forms.input
                    label="Minimum stock"
                    name="minimum_stock"
                    type="number"
                    min="0"
                    :value="$product?->minimum_stock ?? 5"
                    required
                    help="Low stock alerts trigger at or below this level."
                />
            </div>
        </div>

        <x-forms.select
            label="Status"
            name="status"
            :options="App\Enums\ProductStatus::options()"
            :selected="old('status', $product?->status?->value ?? App\Enums\ProductStatus::Active->value)"
            required
        />
    </div>
</div>
