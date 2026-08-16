@extends('layouts.admin', ['heading' => 'Asset Maintenance'])

@section('title', 'Asset Maintenance')

@section('content')
    <x-ui.page-header title="Asset Maintenance" subtitle="Track service and repair history for gym assets">
        <x-slot:actions>
            @can('create', App\Models\AssetMaintenance::class)
                <a href="{{ route('admin.asset-maintenances.create') }}" class="btn btn-primary">
                    Record Maintenance
                </a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-admin.filter-bar class="mb-4">
        <form action="{{ route('admin.asset-maintenances.index') }}" method="GET" class="sg-filter-grid">
            <x-admin.filter-field label="Search" for="search">
                <input
                    type="search"
                    name="search"
                    id="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Asset, provider, description..."
                    class="form-control ps-2"
                >
            </x-admin.filter-field>

            <x-admin.filter-field label="Asset" for="asset_id">
                <select name="asset_id" id="asset_id" class="form-select">
                    <option value="">All assets</option>
                    @foreach ($assets as $asset)
                        <option value="{{ $asset->id }}" @selected((string) ($filters['asset_id'] ?? '') === (string) $asset->id)>
                            {{ $asset->name }} ({{ $asset->asset_code }})
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Type" for="type">
                <select name="type" id="type" class="form-select">
                    <option value="">All types</option>
                    @foreach (App\Enums\AssetMaintenanceType::options() as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="From" for="from_date">
                <input type="date" name="from_date" id="from_date" value="{{ $filters['from_date'] ?? '' }}" class="form-control">
            </x-admin.filter-field>

            <x-admin.filter-field label="To" for="to_date">
                <input type="date" name="to_date" id="to_date" value="{{ $filters['to_date'] ?? '' }}" class="form-control">
            </x-admin.filter-field>

            <x-admin.filter-field label="Actions" class="sg-filter-actions-field">
                <div class="sg-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.asset-maintenances.index') }}" class="btn btn-light">Reset</a>
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
                            <th class="ps-4">Date</th>
                            <th>Asset</th>
                            <th>Type</th>
                            <th class="text-end">Cost</th>
                            <th>Service Provider</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($maintenances as $maintenance)
                            <tr>
                                <td class="ps-4">{{ $maintenance->maintained_at->format('M j, Y') }}</td>
                                <td>
                                    @if ($maintenance->asset)
                                        <a href="{{ route('admin.assets.show', $maintenance->asset) }}" class="fw-semibold text-decoration-none">
                                            {{ $maintenance->asset->name }}
                                        </a>
                                        <div class="small text-muted">{{ $maintenance->asset->asset_code }}</div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $maintenance->type->label() }}</td>
                                <td class="text-end">
                                    @if ($maintenance->cost !== null)
                                        {{ App\Support\MoneyFormatter::format($maintenance->cost, $gymCurrency) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $maintenance->service_provider ?: '—' }}</td>
                                <td class="text-end pe-4">
                                    <x-admin.asset-maintenance-actions :maintenance="$maintenance" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="sg-empty-state">
                                        <h3 class="h6 mb-1">No maintenance records found</h3>
                                        <p class="text-muted small mb-0">Try adjusting your search or filters.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($maintenances->hasPages())
            <div class="card-footer bg-white border-top-0 px-4 py-3">
                {{ $maintenances->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
