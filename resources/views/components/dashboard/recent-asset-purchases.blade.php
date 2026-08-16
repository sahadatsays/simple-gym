@props([
    'assets',
    'currency',
])

<x-dashboard.widget :title="__('dashboard.widgets.recent_asset_purchases')" :subtitle="__('dashboard.widgets.recent_asset_purchases_subtitle')">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 sg-dashboard-table">
            <thead>
                <tr>
                    <th>{{ __('dashboard.widgets.asset') }}</th>
                    <th class="d-none d-sm-table-cell">{{ __('common.table.code') }}</th>
                    <th class="text-end">{{ __('dashboard.widgets.purchase_price') }}</th>
                    <th class="d-none d-md-table-cell">{{ __('common.table.date') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($assets as $asset)
                    <tr>
                        <td>
                            <a href="{{ route('admin.assets.show', $asset) }}" class="fw-semibold text-decoration-none">
                                {{ $asset->name }}
                            </a>
                            <div class="small text-muted d-sm-none">{{ $asset->asset_code }}</div>
                        </td>
                        <td class="d-none d-sm-table-cell text-muted">{{ $asset->asset_code }}</td>
                        <td class="fw-semibold text-end text-nowrap">
                            {{ App\Support\MoneyFormatter::format($asset->purchase_price, $currency) }}
                        </td>
                        <td class="text-nowrap d-none d-md-table-cell text-muted">
                            {{ $asset->purchased_at->format('M j, Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            {{ __('dashboard.widgets.no_asset_purchases_recorded') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($assets->isNotEmpty())
        <div class="text-end mt-3">
            <a href="{{ route('admin.assets.index') }}" class="btn btn-sm btn-light">{{ __('dashboard.widgets.view_all_assets') }}</a>
        </div>
    @endif
</x-dashboard.widget>
