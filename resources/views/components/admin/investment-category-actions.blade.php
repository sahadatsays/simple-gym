@props(['category'])

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
        @can('update', $category)
            <li>
                <a class="dropdown-item" href="{{ route('admin.investment-categories.edit', $category) }}">Edit</a>
            </li>
        @endcan

        <li>
            <a class="dropdown-item" href="{{ route('admin.investments.index', ['investment_category_id' => $category->id]) }}">
                View Investments
            </a>
        </li>

        @can('delete', $category)
            <li><hr class="dropdown-divider"></li>
            <li>
                <form
                    action="{{ route('admin.investment-categories.destroy', $category) }}"
                    method="POST"
                    onsubmit="return confirm('Delete this investment category?');"
                >
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger" @disabled($category->investments_count > 0)>
                        Delete
                    </button>
                </form>
                @if ($category->investments_count > 0)
                    <div class="dropdown-item-text small text-muted px-3 pb-2">
                        Used by {{ $category->investments_count }} investment(s)
                    </div>
                @endif
            </li>
        @endcan
    </ul>
</div>
