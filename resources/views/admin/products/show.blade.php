@extends('layouts.admin', ['heading' => $product->name])

@section('title', $product->name)

@section('content')
    <x-ui.page-header :title="$product->name" :subtitle="$product->sku">
        <x-slot:actions>
            <div class="d-flex flex-wrap gap-2">
                @can('update', $product)
                    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-primary">Edit Product</a>
                @endcan
                <a href="{{ route('admin.products.index') }}" class="btn btn-light">Back to Products</a>
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="row g-3 g-xl-4 mb-4">
        <div class="col-6 col-xl-3">
            <x-dashboard.stat-card
                title="Total Revenue"
                :value="App\Support\MoneyFormatter::format($summary['total_revenue'], $gymCurrency)"
                icon="wallet"
                variant="primary"
                formatted
            />
        </div>
        <div class="col-6 col-xl-3">
            <x-dashboard.stat-card
                title="Units Sold"
                :value="number_format($summary['total_units_sold'])"
                icon="shopping"
                variant="success"
            />
        </div>
        <div class="col-6 col-xl-3">
            <x-dashboard.stat-card
                title="Gross Profit"
                :value="App\Support\MoneyFormatter::format($summary['gross_profit'], $gymCurrency)"
                icon="chart"
                variant="info"
                formatted
            />
        </div>
        <div class="col-6 col-xl-3">
            <x-dashboard.stat-card
                title="This Month"
                :value="App\Support\MoneyFormatter::format($summary['revenue_this_month'], $gymCurrency)"
                icon="chart"
                variant="warning"
                formatted
            />
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 fw-semibold mb-3">Product Details</h2>
                    <dl class="row sg-profile-list mb-0">
                        <dt class="col-5">SKU</dt>
                        <dd class="col-7"><code>{{ $product->sku }}</code></dd>

                        <dt class="col-5">Barcode</dt>
                        <dd class="col-7">{{ $product->barcode ?? '—' }}</dd>

                        <dt class="col-5">Category</dt>
                        <dd class="col-7">{{ $product->category ?? '—' }}</dd>

                        <dt class="col-5">Purchase price</dt>
                        <dd class="col-7">{{ App\Support\MoneyFormatter::format($product->purchase_price, $gymCurrency) }}</dd>

                        <dt class="col-5">Selling price</dt>
                        <dd class="col-7">{{ App\Support\MoneyFormatter::format($product->selling_price, $gymCurrency) }}</dd>

                        <dt class="col-5">Current stock</dt>
                        <dd class="col-7">{{ number_format($product->stock) }}</dd>

                        <dt class="col-5">Minimum stock</dt>
                        <dd class="col-7">{{ number_format($product->minimum_stock) }}</dd>

                        <dt class="col-5">Status</dt>
                        <dd class="col-7">
                            @if ($product->status === App\Enums\ProductStatus::Active)
                                <span class="sg-status-badge sg-status-badge-active">Active</span>
                            @else
                                <span class="sg-status-badge sg-status-badge-inactive">Inactive</span>
                            @endif
                        </dd>

                        <dt class="col-5">Stock status</dt>
                        <dd class="col-7">
                            @if ($product->isOutOfStock())
                                <span class="sg-status-badge sg-status-badge-inactive">Out of stock</span>
                            @elseif ($product->isLowStock())
                                <span class="sg-status-badge sg-status-badge-warning">Low stock</span>
                            @else
                                <span class="text-muted">Healthy</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <h2 class="h6 fw-semibold mb-3">Sales Summary</h2>
                    <dl class="row sg-profile-list mb-0">
                        <dt class="col-6">Total sales</dt>
                        <dd class="col-6">{{ number_format($summary['sales_count']) }}</dd>

                        <dt class="col-6">Avg. order value</dt>
                        <dd class="col-6">{{ App\Support\MoneyFormatter::format($summary['average_order_value'], $gymCurrency) }}</dd>

                        <dt class="col-6">Units this month</dt>
                        <dd class="col-6">{{ number_format($summary['units_sold_this_month']) }}</dd>

                        <dt class="col-6">Total cost</dt>
                        <dd class="col-6">{{ App\Support\MoneyFormatter::format($summary['total_cost'], $gymCurrency) }}</dd>

                        <dt class="col-6">Last sale</dt>
                        <dd class="col-6">
                            {{ $summary['last_sale_at']?->format('M j, Y g:i A') ?? '—' }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-0">
                    <div class="px-4 py-3 border-bottom">
                        <h2 class="h6 fw-semibold mb-0">Monthly Performance</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Month</th>
                                    <th>Units Sold</th>
                                    <th>Revenue</th>
                                    <th class="text-end pe-4">Gross Profit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($monthlyBreakdown as $month)
                                    <tr>
                                        <td class="ps-4">{{ $month['label'] }}</td>
                                        <td>{{ number_format($month['units_sold']) }}</td>
                                        <td>{{ App\Support\MoneyFormatter::format($month['revenue'], $gymCurrency) }}</td>
                                        <td class="text-end pe-4">
                                            {{ App\Support\MoneyFormatter::format($month['gross_profit'], $gymCurrency) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No sales recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-0">
                    <div class="px-4 py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <h2 class="h6 fw-semibold mb-0">Sales History</h2>
                        <form action="{{ route('admin.products.show', $product) }}" method="GET" class="d-flex flex-wrap gap-2">
                            <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="form-control form-control-sm">
                            <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" class="form-control form-control-sm">
                            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                            <a href="{{ route('admin.products.show', $product) }}" class="btn btn-sm btn-light">Reset</a>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Receipt</th>
                                    <th>Customer</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Discount</th>
                                    <th class="text-end pe-4">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sales as $sale)
                                    <tr>
                                        <td class="ps-4 text-muted">{{ $sale->sold_at->format('M j, Y g:i A') }}</td>
                                        <td>
                                            @if ($sale->payment)
                                                <a href="{{ route('admin.payments.show', $sale->payment) }}">
                                                    {{ $sale->payment->receipt_number }}
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if ($sale->member)
                                                <a href="{{ route('admin.members.show', $sale->member) }}">{{ $sale->member->name }}</a>
                                            @else
                                                Walk-in
                                            @endif
                                        </td>
                                        <td>{{ number_format($sale->quantity) }}</td>
                                        <td>{{ App\Support\MoneyFormatter::format($sale->unit_price, $gymCurrency) }}</td>
                                        <td>
                                            @if ((float) $sale->line_discount > 0)
                                                -{{ App\Support\MoneyFormatter::format($sale->line_discount, $gymCurrency) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-end pe-4 fw-semibold">
                                            {{ App\Support\MoneyFormatter::format($sale->line_total, $gymCurrency) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No sales found for this period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($sales->hasPages())
                        <div class="card-footer bg-white border-top-0 px-4 py-3">
                            {{ $sales->withQueryString()->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="px-4 py-3 border-bottom">
                        <h2 class="h6 fw-semibold mb-0">Stock Activity</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th class="text-end pe-4">By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($stockMovements as $movement)
                                    <tr>
                                        <td class="ps-4 text-muted">{{ $movement->created_at->format('M j, Y g:i A') }}</td>
                                        <td>{{ str_replace('.', ' ', ucfirst(str_replace('_', ' ', $movement->action))) }}</td>
                                        <td class="text-muted small">
                                            {{ $movement->description }}
                                            @if ($movement->action === 'product.stock_adjusted' && isset($movement->properties['adjustment']))
                                                ({{ $movement->properties['adjustment'] > 0 ? '+' : '' }}{{ $movement->properties['adjustment'] }})
                                            @endif
                                        </td>
                                        <td class="text-end pe-4">{{ $movement->user?->name ?? 'System' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No stock activity recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
