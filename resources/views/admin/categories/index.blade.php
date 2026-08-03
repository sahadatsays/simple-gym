@extends('layouts.admin', ['heading' => 'Categories'])

@section('title', 'Product Categories')

@section('content')
    <x-ui.page-header title="Product Categories" subtitle="Browse categories and jump to filtered product lists">
        <x-slot:actions>
            @can('create', App\Models\Product::class)
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>
                    Add Product
                </a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <div class="card border-0 shadow-sm sg-data-table-card">
        <div class="card-body p-0">
            <div class="table-responsive sg-data-table-wrapper">
                <table class="table table-hover align-middle mb-0 sg-data-table">
                    <thead>
                        <tr>
                            <th class="ps-4">Category</th>
                            <th>Products</th>
                            <th>Active</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $category->category }}</td>
                                <td>{{ $category->products_count }}</td>
                                <td>{{ $category->active_count }}</td>
                                <td class="text-end pe-4">
                                    <a
                                        href="{{ route('admin.products.index', ['category' => $category->category]) }}"
                                        class="btn btn-sm btn-light"
                                    >
                                        View Products
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">
                                    No categories found yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
