@props(['member'])

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
        @can('view', $member)
            <li>
                <a class="dropdown-item" href="{{ route('admin.members.show', $member) }}">View Profile</a>
            </li>
        @endcan

        @can('update', $member)
            <li>
                <a class="dropdown-item" href="{{ route('admin.members.edit', $member) }}">Edit</a>
            </li>
        @endcan

        @can('delete', $member)
            <li><hr class="dropdown-divider"></li>
            <li>
                <form
                    action="{{ route('admin.members.destroy', $member) }}"
                    method="POST"
                    onsubmit="return confirm('Delete this member?');"
                >
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger">Delete</button>
                </form>
            </li>
        @endcan
    </ul>
</div>
