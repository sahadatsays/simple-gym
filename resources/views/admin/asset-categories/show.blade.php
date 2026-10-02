@extends('layouts.admin', ['heading' => $category->name])

@section('title', $category->name)

@section('content')
    <x-ui.page-header :title="$category->name" subtitle="Assets currently assigned to this category">
        <x-slot:actions>
            @can('update', $category)
                <a href="{{ route('admin.asset-categories.edit', $category) }}" class="btn btn-light">Edit</a>
            @endcan
            <a href="{{ route('admin.asset-categories.index') }}" class="btn btn-light">All Categories</a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <x-dashboard.stat-card title="Assigned Assets" :value="number_format($assets->total())" />
        </div>
        <div class="col-md-4">
            <x-dashboard.stat-card title="Status" :value="$category->is_active ? 'Active' : 'Inactive'" />
        </div>
        <div class="col-md-4">
            <x-dashboard.stat-card title="Sort Order" :value="$category->sort_order" />
        </div>
    </div>

    @if ($category->description)
        <p class="text-muted">{{ $category->description }}</p>
    @endif

    @can('assets.edit')
        <x-ui.card class="mb-4" title="Assign an asset">
            @if ($category->is_active)
                @if ($assignableAssets->isEmpty())
                    <p class="text-muted mb-0">Every asset is already in this category, or no other assets exist yet.</p>
                @else
                    <form action="{{ route('admin.asset-categories.assign', $category) }}" method="POST" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-lg-8">
                            @php
                                $assetOptions = $assignableAssets->mapWithKeys(
                                    fn ($asset): array => [$asset->id => $asset->asset_code.' — '.$asset->name]
                                );
                            @endphp
                            <x-forms.select
                                label="Asset"
                                name="asset_id"
                                :options="$assetOptions"
                                placeholder="Select an asset"
                                help="Moves the asset out of its current category and into this one."
                                required
                            />
                        </div>
                        <div class="col-lg-4">
                            <x-ui.button type="submit" class="mb-3">Assign to Category</x-ui.button>
                        </div>
                    </form>
                @endif
            @else
                <p class="text-muted mb-0">Activate this category before assigning assets. Existing assignments stay in place.</p>
            @endif
        </x-ui.card>
    @endcan

    <div class="card border-0 shadow-sm sg-data-table-card">
        <div class="card-body p-0">
            <div class="table-responsive sg-data-table-wrapper">
                <table class="table table-hover align-middle mb-0 sg-data-table">
                    <thead>
                        <tr>
                            <th class="ps-4">Asset</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Purchase</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assets as $asset)
                            <tr>
                                <td class="ps-4">
                                    @can('view', $asset)
                                        <a href="{{ route('admin.assets.show', $asset) }}" class="fw-semibold text-decoration-none">
                                            {{ $asset->name }}
                                        </a>
                                    @else
                                        <span class="fw-semibold">{{ $asset->name }}</span>
                                    @endcan
                                    <div class="small text-muted">{{ $asset->asset_code }}</div>
                                </td>
                                <td>{{ $asset->location ?: '—' }}</td>
                                <td>{{ $asset->status?->label() ?? '—' }}</td>
                                <td class="text-end pe-4">{{ App\Support\MoneyFormatter::format($asset->purchase_price, $gymCurrency) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="sg-empty-state">
                                        <h3 class="h6 mb-1">No assets assigned</h3>
                                        <p class="text-muted small mb-0">Assign an existing asset, or choose this category when registering equipment.</p>
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
                {{ $assets->links() }}
            </div>
        @endif
    </div>
@endsection
