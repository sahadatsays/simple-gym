@extends('layouts.admin', ['heading' => 'Edit Product'])

@section('title', 'Edit '.$product->name)

@section('content')
    <x-ui.page-header :title="$product->name" subtitle="Update product details and stock levels">
        <x-slot:actions>
            <a href="{{ route('admin.products.index') }}" class="btn btn-light">Back to Products</a>
        </x-slot:actions>
    </x-ui.page-header>

    <form action="{{ route('admin.products.update', $product) }}" method="POST">
        @csrf
        @method('PUT')

        @include('admin.products.partials.form', ['product' => $product, 'categories' => $categories])

        <div class="d-flex flex-wrap gap-2 mt-4">
            <x-ui.button type="submit">Save Changes</x-ui.button>
            @can('update', $product)
                <button
                    type="button"
                    class="btn btn-light"
                    data-bs-toggle="modal"
                    data-bs-target="#adjustStockModal-{{ $product->id }}"
                >
                    Adjust Stock
                </button>
            @endcan
            <a href="{{ route('admin.products.index') }}" class="btn btn-light">Cancel</a>
        </div>
    </form>

    @can('update', $product)
        <div class="modal fade" id="adjustStockModal-{{ $product->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form action="{{ route('admin.products.adjust-stock', $product) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold">Adjust Stock</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small mb-3">
                                Current stock: <strong>{{ number_format($product->stock) }}</strong>
                            </p>
                            <x-forms.input
                                label="Adjustment"
                                name="adjustment"
                                type="number"
                                placeholder="e.g. 10 or -5"
                                required
                                help="Use positive numbers to add stock and negative numbers to remove stock."
                            />
                            <x-forms.textarea label="Notes" name="notes" rows="2" />
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Stock</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection
