@props(['maintenance'])

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
        @can('view', $maintenance)
            <li>
                <a class="dropdown-item" href="{{ route('admin.asset-maintenances.show', $maintenance) }}">View</a>
            </li>
        @endcan

        @if ($maintenance->asset)
            <li>
                <a class="dropdown-item" href="{{ route('admin.assets.show', $maintenance->asset) }}">View Asset</a>
            </li>
        @endif

        @can('update', $maintenance)
            <li>
                <a class="dropdown-item" href="{{ route('admin.asset-maintenances.edit', $maintenance) }}">Edit</a>
            </li>
        @endcan

        @can('delete', $maintenance)
            <li><hr class="dropdown-divider"></li>
            <li>
                <form
                    action="{{ route('admin.asset-maintenances.destroy', $maintenance) }}"
                    method="POST"
                    onsubmit="return confirm('Delete this maintenance record?');"
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
