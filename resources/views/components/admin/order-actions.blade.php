@props(['order'])

<div class="dropdown d-inline-block">
    <button
        class="btn btn-sm btn-light"
        type="button"
        data-bs-toggle="dropdown"
        data-bs-display="static"
        data-bs-popper-config='{"strategy":"fixed"}'
        aria-expanded="false"
        onclick="event.stopPropagation();"
    >
        Actions
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
        @can('view', $order)
            <li>
                <a class="dropdown-item" href="{{ route('admin.orders.show', $order) }}">View Order</a>
            </li>
        @endcan

        @can('delete', $order)
            <li><hr class="dropdown-divider"></li>
            <li>
                <form
                    action="{{ route('admin.orders.destroy', $order) }}"
                    method="POST"
                    onsubmit="return confirm('Delete this order? Stock will be restored and all related payments will be removed.');"
                >
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger">Delete</button>
                </form>
            </li>
        @endcan
    </ul>
</div>
