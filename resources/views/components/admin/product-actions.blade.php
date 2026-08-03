@props(['product'])

<div class="dropdown d-inline-block">
    <button
        class="btn btn-sm btn-light"
        type="button"
        data-bs-toggle="dropdown"
        data-bs-display="static"
        data-bs-popper-config='{"strategy":"fixed"}'
        aria-expanded="false"
    >
        Actions
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
        @can('view', $product)
            <li>
                <a class="dropdown-item" href="{{ route('admin.products.show', $product) }}">View Details</a>
            </li>
        @endcan

        @can('update', $product)
            <li>
                <a class="dropdown-item" href="{{ route('admin.products.edit', $product) }}">Edit</a>
            </li>
            <li>
                <button
                    type="button"
                    class="dropdown-item"
                    data-bs-toggle="modal"
                    data-bs-target="#adjustStockModal-{{ $product->id }}"
                >
                    Adjust Stock
                </button>
            </li>
        @endcan

        @can('delete', $product)
            <li><hr class="dropdown-divider"></li>
            <li>
                <form
                    action="{{ route('admin.products.destroy', $product) }}"
                    method="POST"
                    onsubmit="return confirm('Delete this product?');"
                >
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger">Delete</button>
                </form>
            </li>
        @endcan
    </ul>
</div>
