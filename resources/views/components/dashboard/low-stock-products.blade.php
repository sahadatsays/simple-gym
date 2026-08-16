@props([
    'products',
    'currency',
])

<x-dashboard.widget :title="__('dashboard.widgets.low_stock_products')" :subtitle="__('dashboard.widgets.low_stock_products_subtitle')">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 sg-dashboard-table">
            <thead>
                <tr>
                    <th>{{ __('common.table.product') }}</th>
                    <th class="text-center">{{ __('common.table.stock') }}</th>
                    <th class="text-center d-none d-sm-table-cell">{{ __('common.table.minimum') }}</th>
                    <th class="text-end d-none d-md-table-cell">{{ __('common.table.price') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>
                            <a href="{{ route('admin.products.show', $product) }}" class="fw-semibold text-decoration-none">
                                {{ $product->name }}
                            </a>
                            <div class="small text-muted">{{ $product->sku }}</div>
                        </td>
                        <td class="text-center">
                            <span class="badge text-bg-warning">{{ $product->stock }}</span>
                        </td>
                        <td class="text-center d-none d-sm-table-cell text-muted">{{ $product->minimum_stock }}</td>
                        <td class="text-end d-none d-md-table-cell text-nowrap">
                            {{ App\Support\MoneyFormatter::format($product->selling_price, $currency) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            {{ __('dashboard.widgets.all_above_minimum') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($products->isNotEmpty())
        <div class="text-end mt-3">
            <a href="{{ route('admin.products.index', ['stock' => 'low']) }}" class="btn btn-sm btn-light">{{ __('dashboard.widgets.manage_inventory') }}</a>
        </div>
    @endif
</x-dashboard.widget>
