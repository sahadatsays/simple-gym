@extends('layouts.admin', ['heading' => 'Asset Disposals'])

@section('title', 'Asset Disposals')

@section('content')
    <x-ui.page-header title="Asset Disposals" subtitle="Track sold, disposed, lost, and written-off assets">
        <x-slot:actions>
            @can('create', App\Models\AssetDisposal::class)
                <a href="{{ route('admin.asset-disposals.create') }}" class="btn btn-primary">
                    Dispose Asset
                </a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-admin.filter-bar class="mb-4">
        <form action="{{ route('admin.asset-disposals.index') }}" method="GET" class="sg-filter-grid">
            <x-admin.filter-field label="Search" for="search">
                <input
                    type="search"
                    name="search"
                    id="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Asset, buyer, reason..."
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

            <x-admin.filter-field label="Type" for="disposal_type">
                <select name="disposal_type" id="disposal_type" class="form-select">
                    <option value="">All types</option>
                    @foreach (App\Enums\AssetDisposalType::options() as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['disposal_type'] ?? '') === $value)>
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
                    <a href="{{ route('admin.asset-disposals.index') }}" class="btn btn-light">Reset</a>
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
                            <th class="text-end">Sale Amount</th>
                            <th>Buyer</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($disposals as $disposal)
                            <tr>
                                <td class="ps-4">{{ $disposal->disposed_at->format('M j, Y') }}</td>
                                <td>
                                    @if ($disposal->asset)
                                        <a href="{{ route('admin.assets.show', $disposal->asset) }}" class="fw-semibold text-decoration-none">
                                            {{ $disposal->asset->name }}
                                        </a>
                                        <div class="small text-muted">{{ $disposal->asset->asset_code }}</div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $disposal->disposal_type->label() }}</td>
                                <td class="text-end">
                                    @if ($disposal->sale_amount !== null)
                                        {{ App\Support\MoneyFormatter::format($disposal->sale_amount, $gymCurrency) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $disposal->buyer ?: '—' }}</td>
                                <td class="text-end pe-4">
                                    @can('view', $disposal)
                                        <a href="{{ route('admin.asset-disposals.show', $disposal) }}" class="btn btn-sm btn-light">
                                            View
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="sg-empty-state">
                                        <h3 class="h6 mb-1">No disposal records found</h3>
                                        <p class="text-muted small mb-0">Try adjusting your search or filters.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($disposals->hasPages())
            <div class="card-footer bg-white border-top-0 px-4 py-3">
                {{ $disposals->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
