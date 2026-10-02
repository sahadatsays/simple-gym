@props(['locker'])

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
        @can('view', $locker)
            <li>
                <a class="dropdown-item" href="{{ route('admin.lockers.show', $locker) }}">View</a>
            </li>
        @endcan

        @can('update', $locker)
            <li>
                <a class="dropdown-item" href="{{ route('admin.lockers.edit', $locker) }}">Edit</a>
            </li>
        @endcan

        @can('delete', $locker)
            <li><hr class="dropdown-divider"></li>
            <li>
                <form
                    action="{{ route('admin.lockers.destroy', $locker) }}"
                    method="POST"
                    onsubmit="return confirm('Delete this locker?');"
                >
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger">Delete</button>
                </form>
            </li>
        @endcan
    </ul>
</div>
