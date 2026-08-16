@props([
    'user',
])

<div class="dropdown sg-table-dropdown">
    <button
        type="button"
        class="btn btn-sm btn-outline-secondary sg-action-btn"
        data-bs-toggle="dropdown"
        data-bs-display="static"
        data-bs-popper-config='{"strategy":"fixed","modifiers":[{"name":"offset","options":{"offset":[0,8]}}]}'
        aria-expanded="false"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
            <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0"/>
        </svg>
    </button>

    <ul class="dropdown-menu dropdown-menu-end shadow-sm sg-dropdown-menu">
        @can('update', $user)
            <li>
                <a class="dropdown-item" href="{{ route('admin.users.edit', $user) }}">
                    {{ __('users.edit_user') }}
                </a>
            </li>
        @endcan

        @can('update', $user)
            @if ($user->is_active)
                <li>
                    <form action="{{ route('admin.users.deactivate', $user) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="dropdown-item">{{ __('common.actions.deactivate') }}</button>
                    </form>
                </li>
            @else
                <li>
                    <form action="{{ route('admin.users.activate', $user) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="dropdown-item">{{ __('common.actions.activate') }}</button>
                    </form>
                </li>
            @endif
            <li>
                <button
                    type="button"
                    class="dropdown-item"
                    data-bs-toggle="modal"
                    data-bs-target="#resetPasswordModal-{{ $user->id }}"
                >
                    {{ __('users.reset_password_action') }}
                </button>
            </li>
        @endcan

        @can('delete', $user)
            <li><hr class="dropdown-divider"></li>
            <li>
                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm(@js(__('common.confirm.delete', ['resource' => __('common.table.user')])))">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger">{{ __('common.actions.delete') }}</button>
                </form>
            </li>
        @endcan
    </ul>
</div>
