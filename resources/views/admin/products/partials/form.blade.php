@props(['categories'])

<div class="sg-product-form">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h6 fw-semibold mb-1">Product Information</h2>
                    <p class="text-muted small mb-4">Basic details used across inventory, POS, and reports.</p>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <x-forms.input
                                label="Product name"
                                name="name"
                                placeholder="Whey Protein 1kg"
                                :value="$product?->name"
                                required
                            />
                        </div>
                        <div class="col-md-4">
                            <x-forms.select
                                label="Category"
                                name="category_id"
                                :options="$categories->pluck('name', 'id')->all()"
                                :selected="old('category_id', $product?->category_id)"
                                placeholder="Select category"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-forms.input
                                label="SKU"
                                name="sku"
                                placeholder="Leave blank to auto-generate"
                                :value="$product?->sku"
                                help="Optional. A unique SKU like PRD-000001 will be generated if left empty."
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
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 fw-semibold mb-1">Pricing & Inventory</h2>
                    <p class="text-muted small mb-4">Set purchase and selling prices along with stock levels.</p>

                    <div class="row g-3">
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
                        <div class="col-md-6">
                            <x-forms.input
                                label="Current stock"
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
                        <div class="col-md-6">
                            <x-forms.select
                                label="Status"
                                name="status"
                                :options="App\Enums\ProductStatus::options()"
                                :selected="old('status', $product?->status?->value ?? App\Enums\ProductStatus::Active->value)"
                                required
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sg-product-form-sidebar">
                <div class="card-body p-4">
                    <h2 class="h6 fw-semibold mb-3">Tips</h2>
                    <ul class="small text-muted ps-3 mb-4">
                        <li class="mb-2">Leave SKU empty to auto-generate a unique code.</li>
                        <li class="mb-2">Assign a category to improve POS filtering and reports.</li>
                        <li class="mb-2">Use minimum stock to get dashboard low-stock alerts.</li>
                        <li>Inactive products are hidden from POS but remain in history.</li>
                    </ul>

                    @can('create', App\Models\Category::class)
                        <a href="{{ route('admin.categories.create') }}" class="btn btn-sm btn-light w-100">
                            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>
                            Add Category
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
