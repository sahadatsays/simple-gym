@extends('layouts.admin', ['heading' => 'Products'])

@section('title', 'Products')

@section('content')
    <x-ui.page-header title="Products" subtitle="Manage inventory, pricing, and barcodes">
        <x-slot:actions>
            @can('create', App\Models\Product::class)
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-1" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
                    </svg>
                    Add Product
                </a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-admin.filter-bar class="mb-4">
        <form action="{{ route('admin.products.index') }}" method="GET" class="sg-filter-grid">
            <x-admin.filter-field label="Search" for="search">
                <input
                    type="search"
                    name="search"
                    id="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Name, SKU, barcode, category..."
                    class="form-control ps-2"
                >
            </x-admin.filter-field>

            <x-admin.filter-field label="Barcode" for="barcode">
                <input
                    type="search"
                    name="barcode"
                    id="barcode"
                    value="{{ $filters['barcode'] ?? '' }}"
                    placeholder="Exact barcode match"
                    class="form-control ps-2"
                >
            </x-admin.filter-field>

            <x-admin.filter-field label="Category" for="category_id">
                <select name="category_id" id="category_id" class="form-select">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Stock" for="stock">
                <select name="stock" id="stock" class="form-select">
                    <option value="">All stock levels</option>
                    <option value="available" @selected(($filters['stock'] ?? '') === 'available')>In stock</option>
                    <option value="low" @selected(($filters['stock'] ?? '') === 'low')>Low stock</option>
                    <option value="out" @selected(($filters['stock'] ?? '') === 'out')>Out of stock</option>
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Status" for="status">
                <select name="status" id="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach (App\Enums\ProductStatus::options() as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Actions" class="sg-filter-actions-field">
                <div class="sg-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-light">Reset</a>
                </div>
            </x-admin.filter-field>
        </form>
    </x-admin.filter-bar>

    <div class="card border-0 shadow-sm sg-data-table-card">
        <div class="card-body p-0">
            <div class="table-responsive sg-data-table-wrapper">
                <table class="table table-hover align-middle mb-0 sg-data-table">
                    <thead>
                        <tr>
                            <th class="ps-4">Product</th>
                            <th>SKU</th>
                            <th>Barcode</th>
                            <th>Category</th>
                            <th>Purchase</th>
                            <th>Selling</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td class="ps-4">
                                    <a href="{{ route('admin.products.show', $product) }}" class="fw-semibold text-decoration-none">
                                        {{ $product->name }}
                                    </a>
                                </td>
                                <td><code>{{ $product->sku }}</code></td>
                                <td class="text-muted">{{ $product->barcode ?? '—' }}</td>
                                <td>{{ $product->category?->name ?? '—' }}</td>
                                <td>{{ App\Support\MoneyFormatter::format($product->purchase_price, $gymCurrency) }}</td>
                                <td>{{ App\Support\MoneyFormatter::format($product->selling_price, $gymCurrency) }}</td>
                                <td>
                                    <div class="fw-semibold">{{ number_format($product->stock) }}</div>
                                    @if ($product->isOutOfStock())
                                        <span class="sg-status-badge sg-status-badge-inactive">Out of stock</span>
                                    @elseif ($product->isLowStock())
                                        <span class="sg-status-badge sg-status-badge-warning">Low stock</span>
                                    @else
                                        <span class="text-muted small">Min {{ number_format($product->minimum_stock) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($product->status === App\Enums\ProductStatus::Active)
                                        <span class="sg-status-badge sg-status-badge-active">Active</span>
                                    @else
                                        <span class="sg-status-badge sg-status-badge-inactive">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <x-admin.product-actions :product="$product" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="sg-empty-state">
                                        <h3 class="h6 mb-1">No products found</h3>
                                        <p class="text-muted small mb-0">Try adjusting your search or filters.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($products->hasPages())
            <div class="card-footer bg-white border-top-0 px-4 py-3">
                {{ $products->withQueryString()->links() }}
            </div>
        @endif
    </div>

    @foreach ($products as $product)
        @can('update', $product)
            <div class="modal fade" id="adjustStockModal-{{ $product->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <form action="{{ route('admin.products.adjust-stock', $product) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title fw-bold">Adjust Stock</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted small mb-3">
                                    Adjust stock for <strong>{{ $product->name }}</strong>.
                                    Current stock: <strong>{{ number_format($product->stock) }}</strong>
                                </p>
                                <x-forms.input
                                    label="Adjustment"
                                    name="adjustment"
                                    type="number"
                                    placeholder="e.g. 10 or -5"
                                    required
                                    help="Use positive numbers to add stock and negative numbers to remove stock."
                                />
                                <x-forms.textarea label="Notes" name="notes" rows="2" />
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update Stock</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    @endforeach
@endsection
