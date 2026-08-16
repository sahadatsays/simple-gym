@extends('layouts.admin', ['heading' => 'Investment Categories'])

@section('title', 'Investment Categories')

@section('content')
    <x-ui.page-header title="Investment Categories" subtitle="Organize investments for reporting and filtering">
        <x-slot:actions>
            @can('create', App\Models\InvestmentCategory::class)
                <a href="{{ route('admin.investment-categories.create') }}" class="btn btn-primary">
                    Add Category
                </a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    @if ($errors->has('category'))
        <div class="alert alert-danger">{{ $errors->first('category') }}</div>
    @endif

    <x-admin.filter-bar class="mb-4">
        <form action="{{ route('admin.investment-categories.index') }}" method="GET" class="sg-filter-grid">
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
                    <a href="{{ route('admin.investment-categories.index') }}" class="btn btn-light">Reset</a>
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
                            <th>Investments</th>
                            <th>Sort</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold">{{ $category->name }}</div>
                                    @if ($category->description)
                                        <div class="small text-muted">{{ $category->description }}</div>
                                    @endif
                                </td>
                                <td>{{ number_format($category->investments_count) }}</td>
                                <td>{{ $category->sort_order }}</td>
                                <td>
                                    @if ($category->is_active)
                                        <span class="sg-status-badge sg-status-badge-active">Active</span>
                                    @else
                                        <span class="sg-status-badge sg-status-badge-inactive">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <x-admin.investment-category-actions :category="$category" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="sg-empty-state">
                                        <h3 class="h6 mb-1">No investment categories found</h3>
                                        <p class="text-muted small mb-0">Try adjusting your search or filters.</p>
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
