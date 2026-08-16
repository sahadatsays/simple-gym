@extends('layouts.admin', ['heading' => 'Assets'])

@section('title', 'Assets')

@section('content')
    <x-ui.page-header title="Assets" subtitle="Track gym equipment, furniture, and facility assets">
        <x-slot:actions>
            @can('create', App\Models\Asset::class)
                <a href="{{ route('admin.assets.create') }}" class="btn btn-primary">
                    Add Asset
                </a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-admin.filter-bar class="mb-4">
        <form action="{{ route('admin.assets.index') }}" method="GET" class="sg-filter-grid">
            <x-admin.filter-field label="Search" for="search">
                <input
                    type="search"
                    name="search"
                    id="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Code, name, supplier, location..."
                    class="form-control ps-2"
                >
            </x-admin.filter-field>

            <x-admin.filter-field label="Category" for="asset_category_id">
                <select name="asset_category_id" id="asset_category_id" class="form-select">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['asset_category_id'] ?? '') === (string) $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Status" for="status">
                <select name="status" id="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach (App\Enums\AssetStatus::options() as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Condition" for="condition">
                <select name="condition" id="condition" class="form-select">
                    <option value="">All conditions</option>
                    @foreach (App\Enums\AssetCondition::options() as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['condition'] ?? '') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Location" for="location">
                <select name="location" id="location" class="form-select">
                    <option value="">All locations</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location }}" @selected(($filters['location'] ?? '') === $location)>
                            {{ $location }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Actions" class="sg-filter-actions-field">
                <div class="sg-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.assets.index') }}" class="btn btn-light">Reset</a>
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
                            <th class="ps-4">Asset</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Condition</th>
                            <th>Status</th>
                            <th class="text-end">Purchase</th>
                            <th class="text-end">Current</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assets as $asset)
                            <tr>
                                <td class="ps-4">
                                    <a href="{{ route('admin.assets.show', $asset) }}" class="fw-semibold text-decoration-none">
                                        {{ $asset->name }}
                                    </a>
                                    <div class="small text-muted">{{ $asset->asset_code }}</div>
                                </td>
                                <td>{{ $asset->category?->name ?? '—' }}</td>
                                <td>{{ $asset->location ?: '—' }}</td>
                                <td>{{ $asset->condition?->label() ?? '—' }}</td>
                                <td>
                                    @if ($asset->status === App\Enums\AssetStatus::Active)
                                        <span class="sg-status-badge sg-status-badge-active">{{ $asset->status->label() }}</span>
                                    @else
                                        <span class="sg-status-badge sg-status-badge-inactive">{{ $asset->status->label() }}</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ App\Support\MoneyFormatter::format($asset->purchase_price, $gymCurrency) }}</td>
                                <td class="text-end">{{ App\Support\MoneyFormatter::format($asset->current_value ?? 0, $gymCurrency) }}</td>
                                <td class="text-end pe-4">
                                    <x-admin.asset-actions :asset="$asset" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="sg-empty-state">
                                        <h3 class="h6 mb-1">No assets found</h3>
                                        <p class="text-muted small mb-0">Try adjusting your search or filters.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($assets->hasPages())
            <div class="card-footer bg-white border-top-0 px-4 py-3">
                {{ $assets->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
