@extends('layouts.admin', ['heading' => 'Asset Categories'])

@section('title', 'Asset Categories')

@section('content')
    <x-ui.page-header title="Asset Categories" subtitle="Group gym equipment for assignment, filtering, and value reports">
        <x-slot:actions>
            @can('create', App\Models\AssetCategory::class)
                <a href="{{ route('admin.asset-categories.create') }}" class="btn btn-primary">
                    Add Category
                </a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    @if ($errors->has('category'))
        <div class="alert alert-danger">{{ $errors->first('category') }}</div>
    @endif

    <x-admin.filter-bar class="mb-4">
        <form action="{{ route('admin.asset-categories.index') }}" method="GET" class="sg-filter-grid">
            <x-admin.filter-field label="Search" for="search">
                <input
                    type="search"
                    name="search"
                    id="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Category name or description..."
                    class="form-control ps-2"
                >
            </x-admin.filter-field>

            <x-admin.filter-field label="Status" for="status">
                <select name="status" id="status" class="form-select">
                    <option value="">All statuses</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Actions" class="sg-filter-actions-field">
                <div class="sg-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.asset-categories.index') }}" class="btn btn-light">Reset</a>
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
                            <th class="ps-4">Category</th>
                            <th>Assets</th>
                            <th>Sort</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr>
                                <td class="ps-4">
                                    <a href="{{ route('admin.asset-categories.show', $category) }}" class="fw-semibold text-decoration-none">
                                        {{ $category->name }}
                                    </a>
                                    @if ($category->description)
                                        <div class="small text-muted">{{ $category->description }}</div>
                                    @endif
                                </td>
                                <td>{{ number_format($category->assets_count) }}</td>
                                <td>{{ $category->sort_order }}</td>
                                <td>
                                    @if ($category->is_active)
                                        <span class="sg-status-badge sg-status-badge-active">Active</span>
                                    @else
                                        <span class="sg-status-badge sg-status-badge-inactive">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <x-admin.asset-category-actions :category="$category" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="sg-empty-state">
                                        <h3 class="h6 mb-1">No asset categories found</h3>
                                        <p class="text-muted small mb-0">Add a category before registering equipment.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($categories->hasPages())
            <div class="card-footer bg-white border-top-0 px-4 py-3">
                {{ $categories->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
