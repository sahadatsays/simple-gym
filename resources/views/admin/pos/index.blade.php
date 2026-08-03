@extends('layouts.admin', ['heading' => 'Point of Sale'])

@section('title', 'Point of Sale')

@section('content')
    <div
        class="sg-pos"
        x-data="posTerminal({
            initialProducts: @js($initialProducts),
            categories: @js($categories),
            members: @js($members->map(fn ($member) => ['id' => (string) $member->id, 'label' => $member->name.' ('.$member->member_code.')'])->values()->all()),
            currencySymbol: @js(App\Support\MoneyFormatter::symbol($gymCurrency)),
            routes: {
                search: @js(route('admin.pos.products.search')),
                scan: @js(route('admin.pos.products.scan')),
            },
        })"
    >
        <div class="sg-pos-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Point of Sale</h1>
                <p class="text-muted mb-0">Search products, scan barcodes, and complete sales quickly.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill text-bg-primary px-3 py-2" x-show="cartCount > 0">
                    <span x-text="cartCount"></span> in cart
                </span>
                <a href="{{ route('admin.payments.index') }}" class="btn btn-light">Payment History</a>
            </div>
        </div>

        <div class="row g-4 sg-pos-layout">
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm sg-pos-panel mb-3">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-7">
                                <label for="barcode-input" class="form-label fw-semibold">Barcode Scanner</label>
                                <div class="input-group input-group-lg sg-pos-scan-group">
                                    <span class="input-group-text bg-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                            <path d="M1.5 1a.5.5 0 0 0-.5.5v3a.5.5 0 0 1-1 0v-3A1.5 1.5 0 0 1 1.5 0h3a.5.5 0 0 1 0 1zM11 1.5a.5.5 0 0 1 .5-.5h3A1.5 1.5 0 0 1 16 2.5v3a.5.5 0 0 1-1 0v-3a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 1-.5-.5M1.5 11a.5.5 0 0 1 .5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 1 0 1h-3A1.5 1.5 0 0 1 0 14.5v-3a.5.5 0 0 1 .5-.5m14 0a.5.5 0 0 1 .5.5v3a1.5 1.5 0 0 1-1.5 1.5h-3a.5.5 0 0 1 0-1h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 1 .5-.5"/>
                                            <path d="M3 4.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5z"/>
                                        </svg>
                                    </span>
                                    <input
                                        type="text"
                                        id="barcode-input"
                                        x-ref="barcodeInput"
                                        x-model="barcodeInput"
                                        @keydown.enter.prevent="scanBarcode()"
                                        class="form-control"
                                        placeholder="Scan barcode or enter SKU, then press Enter"
                                        autocomplete="off"
                                    >
                                    <button type="button" class="btn btn-primary" @click="scanBarcode()" :disabled="isScanning">
                                        Add
                                    </button>
                                </div>
                                <div class="text-danger small mt-2" x-show="scanError" x-text="scanError" x-cloak></div>
                            </div>
                            <div class="col-lg-5">
                                <label for="product-search" class="form-label fw-semibold">Search Products</label>
                                <input
                                    type="search"
                                    id="product-search"
                                    x-model="searchQuery"
                                    @input="scheduleSearch()"
                                    class="form-control form-control-lg"
                                    placeholder="Name, SKU, or barcode"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button
                        type="button"
                        class="btn btn-sm"
                        :class="selectedCategory === '' ? 'btn-primary' : 'btn-light'"
                        @click="selectedCategory = ''; fetchProducts()"
                    >
                        All
                    </button>
                    <template x-for="category in categories" :key="category">
                        <button
                            type="button"
                            class="btn btn-sm"
                            :class="selectedCategory === category ? 'btn-primary' : 'btn-light'"
                            @click="selectedCategory = category; fetchProducts()"
                            x-text="category"
                        ></button>
                    </template>
                </div>

                <div class="sg-pos-product-grid" x-show="! isSearching">
                    <template x-for="product in products" :key="product.id">
                        <button type="button" class="sg-pos-product-card" @click="addProduct(product)">
                            <div class="sg-pos-product-card-top">
                                <span class="sg-pos-product-category" x-text="product.category ?? 'General'"></span>
                                <span class="sg-pos-product-stock" :class="{ 'text-warning': product.is_low_stock }">
                                    <span x-text="product.stock"></span> left
                                </span>
                            </div>
                            <div class="sg-pos-product-name" x-text="product.name"></div>
                            <div class="sg-pos-product-meta">
                                <span x-text="product.sku"></span>
                            </div>
                            <div class="sg-pos-product-price" x-text="formatMoney(product.selling_price)"></div>
                        </button>
                    </template>
                </div>

                <div class="text-center py-5 text-muted" x-show="isSearching" x-cloak>
                    Loading products...
                </div>

                <div class="text-center py-5" x-show="! isSearching && products.length === 0" x-cloak>
                    <h2 class="h6 mb-1">No products found</h2>
                    <p class="text-muted small mb-0">Try another search term or category.</p>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card border-0 shadow-sm sg-pos-cart sticky-top">
                    <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="h5 fw-bold mb-0">Cart</h2>
                            <button type="button" class="btn btn-sm btn-light" @click="clearCart()" x-show="cart.length > 0" x-cloak>
                                Clear
                            </button>
                        </div>
                    </div>

                    <div class="card-body px-4">
                        <div class="sg-pos-cart-empty text-center py-5" x-show="cart.length === 0">
                            <div class="text-muted mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                    <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                                </svg>
                            </div>
                            <p class="text-muted mb-0">Scan a barcode or tap a product to begin.</p>
                        </div>

                        <div class="sg-pos-cart-items" x-show="cart.length > 0" x-cloak>
                            <template x-for="item in cart" :key="item.product_id">
                                <div class="sg-pos-cart-item">
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold" x-text="item.name"></div>
                                        <div class="small text-muted" x-text="item.sku"></div>
                                        <div class="small text-muted" x-text="formatMoney(item.unit_price) + ' each'"></div>
                                    </div>
                                    <div class="sg-pos-qty-controls">
                                        <button type="button" class="btn btn-sm btn-light" @click="decrementItem(item.product_id)">−</button>
                                        <span class="sg-pos-qty-value" x-text="item.quantity"></span>
                                        <button type="button" class="btn btn-sm btn-light" @click="incrementItem(item.product_id)">+</button>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-semibold" x-text="formatMoney(item.unit_price * item.quantity)"></div>
                                        <button type="button" class="btn btn-link btn-sm text-danger p-0" @click="removeItem(item.product_id)">
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="text-danger small mt-3" x-show="cartError" x-text="cartError" x-cloak></div>
                        @if ($errors->has('cart'))
                            <div class="text-danger small mt-3">{{ $errors->first('cart') }}</div>
                        @endif
                        @if ($errors->has('items') || $errors->has('items.0.quantity'))
                            <div class="text-danger small mt-3">
                                {{ $errors->first('items') ?: $errors->first('items.0.quantity') }}
                            </div>
                        @endif
                    </div>

                    <div class="card-footer bg-white border-0 px-4 pb-4" x-show="cart.length > 0" x-cloak>
                        <form
                            x-ref="checkoutForm"
                            action="{{ route('admin.pos.store') }}"
                            method="POST"
                        >
                            @csrf

                            <template x-for="(item, index) in cart" :key="'form-' + item.product_id">
                                <div>
                                    <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                                    <input type="hidden" :name="`items[${index}][quantity]`" :value="item.quantity">
                                </div>
                            </template>

                            <div class="mb-3">
                                <label class="form-label">Member (optional)</label>
                                <select name="member_id" x-model="memberId" class="form-select">
                                    <option value="">Walk-in customer</option>
                                    <template x-for="member in members" :key="member.id">
                                        <option :value="member.id" x-text="member.label"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label">Discount</label>
                                    <input type="number" name="discount_amount" x-model="discountAmount" min="0" step="0.01" class="form-control">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Payment method</label>
                                    <select name="payment_method" x-model="paymentMethod" class="form-select" required>
                                        @foreach ($enabledPaymentMethods as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Reference</label>
                                <input type="text" name="payment_reference" x-model="paymentReference" class="form-control" placeholder="Optional">
                            </div>

                            <div class="sg-pos-totals mb-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Subtotal</span>
                                    <span x-text="formatMoney(subtotal)"></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2" x-show="Number(discountAmount) > 0">
                                    <span class="text-muted">Discount</span>
                                    <span class="text-danger" x-text="'-' + formatMoney(discountAmount)"></span>
                                </div>
                                <div class="d-flex justify-content-between fw-bold fs-5">
                                    <span>Total Due</span>
                                    <span x-text="formatMoney(totalDue)"></span>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="btn btn-primary btn-lg w-100"
                                @click="submitSale()"
                                :disabled="isSubmitting || cart.length === 0"
                            >
                                <span x-show="! isSubmitting">Complete Sale & Print Receipt</span>
                                <span x-show="isSubmitting" x-cloak>Processing...</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        .sg-pos-layout {
            align-items: flex-start;
        }

        .sg-pos-panel,
        .sg-pos-cart {
            border-radius: 1rem;
        }

        .sg-pos-cart {
            top: 1rem;
        }

        .sg-pos-scan-group .form-control {
            font-size: 1rem;
        }

        .sg-pos-product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1rem;
        }

        .sg-pos-product-card {
            border: 1px solid #e2e8f0;
            background: #fff;
            border-radius: 1rem;
            padding: 1rem;
            text-align: left;
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .sg-pos-product-card:hover {
            transform: translateY(-2px);
            border-color: #93c5fd;
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.12);
        }

        .sg-pos-product-card-top {
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .sg-pos-product-category,
        .sg-pos-product-stock {
            font-size: 0.75rem;
            color: #64748b;
        }

        .sg-pos-product-name {
            font-weight: 600;
            color: #0f172a;
            min-height: 2.8rem;
            margin-bottom: 0.5rem;
        }

        .sg-pos-product-meta {
            font-size: 0.8rem;
            color: #94a3b8;
            margin-bottom: 0.75rem;
        }

        .sg-pos-product-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2563eb;
        }

        .sg-pos-cart-item {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto auto;
            gap: 0.75rem;
            align-items: center;
            padding: 0.875rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .sg-pos-cart-item:last-child {
            border-bottom: 0;
        }

        .sg-pos-qty-controls {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #f8fafc;
            border-radius: 999px;
            padding: 0.25rem;
        }

        .sg-pos-qty-value {
            min-width: 1.5rem;
            text-align: center;
            font-weight: 600;
        }

        .sg-pos-totals {
            padding-top: 1rem;
            border-top: 1px dashed #e2e8f0;
        }

        @media (max-width: 1199.98px) {
            .sg-pos-cart {
                position: static !important;
            }
        }
    </style>
@endpush
