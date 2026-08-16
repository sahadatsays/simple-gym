@extends('layouts.admin', ['heading' => 'Categories'])

@section('title', 'Product Categories')

@section('content')
    <x-ui.page-header title="Product Categories" subtitle="Organize inventory for POS, filters, and reports">
        <x-slot:actions>
            @can('create', App\Models\Category::class)
                <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>
                    Add Category
                </a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    @if ($errors->has('category'))
        <div class="alert alert-danger">{{ $errors->first('category') }}</div>
    @endif

    <div class="card border-0 shadow-sm sg-data-table-card">
        <div class="card-body p-0">
            <div class="table-responsive sg-data-table-wrapper">
                <table class="table table-hover align-middle mb-0 sg-data-table">
                    <thead>
                        <tr>
                            <th class="ps-4">Category</th>
                            <th>Products</th>
                            <th>Active</th>
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
                                <td>{{ number_format($category->products_count) }}</td>
                                <td>{{ number_format($category->active_products_count) }}</td>
                                <td>
                                    @if ($category->is_active)
                                        <span class="sg-status-badge sg-status-badge-active">Active</span>
                                    @else
                                        <span class="sg-status-badge sg-status-badge-inactive">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <x-admin.category-actions :category="$category" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="sg-empty-state">
                                        <h3 class="h6 mb-1">No categories yet</h3>
                                        <p class="text-muted small mb-0">Create categories to organize your products.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
