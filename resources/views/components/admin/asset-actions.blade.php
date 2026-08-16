@props(['asset'])

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
        @can('view', $asset)
            <li>
                <a class="dropdown-item" href="{{ route('admin.assets.show', $asset) }}">View</a>
            </li>
        @endcan

        @can('update', $asset)
            <li>
                <a class="dropdown-item" href="{{ route('admin.assets.edit', $asset) }}">Edit</a>
            </li>
        @endcan

        @can('delete', $asset)
            @if ($asset->isDeletable())
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form
                        action="{{ route('admin.assets.destroy', $asset) }}"
                        method="POST"
                        onsubmit="return confirm('Delete this asset?');"
                    >
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger">
                            Delete
                        </button>
                    </form>
                </li>
            @endif
        @endcan
    </ul>
</div>
