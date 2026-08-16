@extends('layouts.admin', ['heading' => 'Asset Details'])

@section('title', $asset->asset_code)

@section('content')
    @php
        $totalMaintenanceCost = $asset->maintenances->sum(fn ($maintenance): float => (float) ($maintenance->cost ?? 0));
        $isDisposed = $asset->disposal !== null || $asset->status === App\Enums\AssetStatus::Disposed;
    @endphp

    <x-ui.page-header :title="$asset->name" :subtitle="$asset->asset_code">
        <x-slot:actions>
            <div class="d-flex flex-wrap gap-2">
                @can('update', $asset)
                    <a href="{{ route('admin.assets.edit', $asset) }}" class="btn btn-primary">Edit</a>
                @endcan
                <a href="{{ route('admin.assets.index') }}" class="btn btn-light">Back to Assets</a>
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="row g-3 g-xl-4 mb-4">
        <div class="col-12 col-md-4">
            <x-dashboard.stat-card
                title="Current Value"
                :value="App\Support\MoneyFormatter::format($asset->current_value ?? 0, $gymCurrency)"
                icon="wallet"
                variant="primary"
                formatted
            />
        </div>
        <div class="col-12 col-md-4">
            <x-dashboard.stat-card
                title="Total Maintenance Cost"
                :value="App\Support\MoneyFormatter::format($totalMaintenanceCost, $gymCurrency)"
                icon="chart"
                variant="warning"
                formatted
            />
        </div>
        <div class="col-12 col-md-4">
            <x-dashboard.stat-card
                title="Total Purchase Cost"
                :value="App\Support\MoneyFormatter::format($asset->purchase_price, $gymCurrency)"
                icon="shopping"
                variant="info"
                formatted
            />
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <x-admin.detail-section title="Basic Information">
                <x-admin.detail-list>
                    <x-admin.detail-item label="Asset code" :value="$asset->asset_code" />
                    <x-admin.detail-item label="Asset name" :value="$asset->name" />
                    <x-admin.detail-item label="Category" :value="$asset->category?->name ?? '—'" />
                    <x-admin.detail-item label="Status">
                        @if ($asset->status === App\Enums\AssetStatus::Active)
                            <span class="sg-status-badge sg-status-badge-active">{{ $asset->status->label() }}</span>
                        @else
                            <span class="sg-status-badge sg-status-badge-inactive">{{ $asset->status->label() }}</span>
                        @endif
                    </x-admin.detail-item>
                    <x-admin.detail-item label="Condition" :value="$asset->condition?->label() ?? '—'" />
                    <x-admin.detail-item label="Location" :value="$asset->location ?: '—'" />
                </x-admin.detail-list>
            </x-admin.detail-section>
        </div>

        <div class="col-lg-6">
            <x-admin.detail-section title="Purchase Information">
                <x-admin.detail-list>
                    <x-admin.detail-item label="Purchase date" :value="$asset->purchased_at->format('M j, Y')" />
                    <x-admin.detail-item
                        label="Purchase price"
                        :value="App\Support\MoneyFormatter::format($asset->purchase_price, $gymCurrency)"
                    />
                    <x-admin.detail-item label="Supplier" :value="$asset->supplier ?: '—'" />
                    <x-admin.detail-item
                        label="Warranty expiry"
                        :value="$asset->warranty_expires_at?->format('M j, Y') ?? '—'"
                    />
                </x-admin.detail-list>
            </x-admin.detail-section>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <x-admin.detail-section title="Financial Information">
                <x-admin.detail-list>
                    <x-admin.detail-item
                        label="Current value"
                        :value="App\Support\MoneyFormatter::format($asset->current_value ?? 0, $gymCurrency)"
                    />
                    <x-admin.detail-item
                        label="Total maintenance cost"
                        :value="App\Support\MoneyFormatter::format($totalMaintenanceCost, $gymCurrency)"
                    />
                    <x-admin.detail-item
                        label="Total purchase cost"
                        :value="App\Support\MoneyFormatter::format($asset->purchase_price, $gymCurrency)"
                    />
                </x-admin.detail-list>
            </x-admin.detail-section>
        </div>

        <div class="col-lg-6">
            <x-admin.detail-section title="Disposal Information">
                <x-admin.detail-list>
                    <x-admin.detail-item label="Disposal status">
                        @if ($isDisposed)
                            <span class="sg-status-badge sg-status-badge-inactive">Disposed</span>
                        @else
                            <span class="sg-status-badge sg-status-badge-active">Not disposed</span>
                        @endif
                    </x-admin.detail-item>
                    <x-admin.detail-item
                        label="Disposal date"
                        :value="$asset->disposal?->disposed_at->format('M j, Y') ?? '—'"
                    />
                    <x-admin.detail-item
                        label="Disposal type"
                        :value="$asset->disposal?->disposal_type->label() ?? '—'"
                    />
                    <x-admin.detail-item label="Sale amount">
                        @if ($asset->disposal?->sale_amount !== null)
                            {{ App\Support\MoneyFormatter::format($asset->disposal->sale_amount, $gymCurrency) }}
                        @else
                            —
                        @endif
                    </x-admin.detail-item>
                </x-admin.detail-list>
            </x-admin.detail-section>
        </div>
    </div>

    <x-admin.detail-table-section title="Maintenance History" class="mb-4">
        <table class="table table-hover align-middle mb-0 sg-data-table">
            <thead>
                <tr>
                    <th class="ps-4">Date</th>
                    <th>Type</th>
                    <th class="text-end">Cost</th>
                    <th>Service Provider</th>
                    <th class="pe-4">Description</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($asset->maintenances as $maintenance)
                    <tr>
                        <td class="ps-4">{{ $maintenance->maintained_at->format('M j, Y') }}</td>
                        <td>{{ $maintenance->type->label() }}</td>
                        <td class="text-end">
                            @if ($maintenance->cost !== null)
                                {{ App\Support\MoneyFormatter::format($maintenance->cost, $gymCurrency) }}
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $maintenance->service_provider ?: '—' }}</td>
                        <td class="pe-4">
                            @if ($maintenance->description)
                                <span class="d-inline-block text-truncate" style="max-width: 280px;">
                                    {{ $maintenance->description }}
                                </span>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="sg-empty-state">
                                <h3 class="h6 mb-1">No maintenance records</h3>
                                <p class="text-muted small mb-0">Maintenance history will appear here once recorded.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-admin.detail-table-section>

    @if ($asset->notes || $asset->creator)
        <x-admin.detail-section title="Additional Information">
            <x-admin.detail-list>
                @if ($asset->creator)
                    <x-admin.detail-item label="Created by" :value="$asset->creator->name" />
                @endif
                @if ($asset->created_at)
                    <x-admin.detail-item label="Recorded" :value="$asset->created_at->format('M j, Y g:i A')" />
                @endif
            </x-admin.detail-list>

            @if ($asset->notes)
                <div class="mt-3 pt-3 border-top">
                    <h3 class="h6 fw-semibold mb-2">Notes</h3>
                    <p class="mb-0 text-muted">{{ $asset->notes }}</p>
                </div>
            @endif
        </x-admin.detail-section>
    @endif
@endsection
