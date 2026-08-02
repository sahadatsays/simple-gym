@props(['plan'])

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
        @can('update', $plan)
            <li>
                <a class="dropdown-item" href="{{ route('admin.membership-plans.edit', $plan) }}">Edit</a>
            </li>
        @endcan

        @can('delete', $plan)
            <li><hr class="dropdown-divider"></li>
            <li>
                <form
                    action="{{ route('admin.membership-plans.destroy', $plan) }}"
                    method="POST"
                    onsubmit="return confirm('Delete this membership plan?');"
                >
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger" @disabled($plan->members_count > 0)>
                        Delete
                    </button>
                </form>
                @if ($plan->members_count > 0)
                    <div class="dropdown-item-text small text-muted px-3 pb-2">
                        Assigned to {{ $plan->members_count }} member(s)
                    </div>
                @endif
            </li>
        @endcan
    </ul>
</div>
