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
        @can('view', $category)
            <li>
                <a class="dropdown-item" href="{{ route('admin.asset-categories.show', $category) }}">View Assets</a>
            </li>
        @endcan

        @can('update', $category)
            <li>
                <a class="dropdown-item" href="{{ route('admin.asset-categories.edit', $category) }}">Edit</a>
            </li>
        @endcan

        @can('viewAny', App\Models\Asset::class)
            <li>
                <a class="dropdown-item" href="{{ route('admin.assets.index', ['asset_category_id' => $category->id]) }}">
                    Open in Assets
                </a>
            </li>
        @endcan

        @can('delete', $category)
            <li><hr class="dropdown-divider"></li>
            <li>
                <form
                    action="{{ route('admin.asset-categories.destroy', $category) }}"
                    method="POST"
                    onsubmit="return confirm('Delete this asset category?');"
                >
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger" @disabled($category->assigned_assets_count > 0)>
                        Delete
                    </button>
                </form>
                @if ($category->assigned_assets_count > 0)
                    <div class="dropdown-item-text small text-muted px-3 pb-2">
                        Assigned to {{ $category->assigned_assets_count }} asset(s)
                    </div>
                @endif
            </li>
        @endcan
    </ul>
</div>
