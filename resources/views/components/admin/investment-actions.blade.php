@props(['investment'])

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
        @can('view', $investment)
            <li>
                <a class="dropdown-item" href="{{ route('admin.investments.show', $investment) }}">View</a>
            </li>
        @endcan

        @can('update', $investment)
            <li>
                <a class="dropdown-item" href="{{ route('admin.investments.edit', $investment) }}">Edit</a>
            </li>
        @endcan

        @can('delete', $investment)
            <li><hr class="dropdown-divider"></li>
            <li>
                <form
                    action="{{ route('admin.investments.destroy', $investment) }}"
                    method="POST"
                    onsubmit="return confirm('Delete this investment?');"
                >
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger">
                        Delete
                    </button>
                </form>
            </li>
        @endcan
    </ul>
</div>
