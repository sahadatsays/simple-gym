@props(['borrowing'])

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
        @can('view', $borrowing)
            <li>
                <a class="dropdown-item" href="{{ route('admin.borrowings.show', $borrowing) }}">View</a>
            </li>
        @endcan

        @can('update', $borrowing)
            <li>
                <a class="dropdown-item" href="{{ route('admin.borrowings.edit', $borrowing) }}">Edit</a>
            </li>
        @endcan

        @can('delete', $borrowing)
            <li><hr class="dropdown-divider"></li>
            <li>
                <form
                    action="{{ route('admin.borrowings.destroy', $borrowing) }}"
                    method="POST"
                    onsubmit="return confirm('Delete this borrowing? Borrowings with repayment history cannot be deleted.');"
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
