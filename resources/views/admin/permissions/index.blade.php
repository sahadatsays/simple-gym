@extends('layouts.admin', ['heading' => __('roles.permissions.title')])

@section('title', __('roles.permissions.title'))

@section('content')
    <x-ui.page-header :title="__('roles.permissions.title')" :subtitle="__('roles.permissions.subtitle')">
        <x-slot:actions>
            @can('create', Spatie\Permission\Models\Permission::class)
                <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary">
                    {{ __('roles.permissions.add_custom') }}
                </a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <div class="sg-permission-grid">
        @foreach ($groupedPermissions as $group => $permissions)
            <div class="sg-permission-group card border-0 shadow-sm">
                <div class="sg-permission-group__header">
                    <h2 class="h6 mb-0 fw-semibold">{{ \App\Support\PermissionRegistry::groupLabel($group) }}</h2>
                    <span class="badge text-bg-light">{{ $permissions->count() }}</span>
                </div>

                <div class="sg-permission-group__body">
                    <div class="row g-2">
                        @foreach ($permissions as $permission)
                            @php($isDefault = \App\Support\PermissionRegistry::isDefault($permission->name))

                            <div class="col-sm-6 col-xl-4">
                                <div class="sg-permission-card @if($isDefault) sg-permission-card--system @endif">
                                    <div class="sg-permission-card__content">
                                        <div class="d-flex align-items-start justify-content-between gap-2">
                                            <div class="sg-permission-item__label">
                                                {{ \App\Support\PermissionRegistry::permissionLabel($permission->name) }}
                                            </div>
                                            @if ($isDefault)
                                                <span class="badge text-bg-secondary">{{ __('roles.system_badge') }}</span>
                                            @else
                                                <span class="badge text-bg-primary">{{ __('roles.custom_badge') }}</span>
                                            @endif
                                        </div>
                                        <div class="sg-permission-item__slug">{{ $permission->name }}</div>
                                        <div class="small text-muted mt-1">
                                            {{ __('roles.guard') }}: {{ $permission->guard_name }}
                                        </div>
                                    </div>

                                    @unless ($isDefault)
                                        <div class="sg-permission-card__actions">
                                            @can('update', $permission)
                                                <a href="{{ route('admin.permissions.edit', $permission) }}" class="btn btn-sm btn-outline-primary">
                                                    {{ __('common.actions.edit') }}
                                                </a>
                                            @endcan
                                            @can('delete', $permission)
                                                <form action="{{ route('admin.permissions.destroy', $permission) }}" method="POST" onsubmit="return confirm(@js(__('roles.delete_permission_confirm')))">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        {{ __('common.actions.delete') }}
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    @endunless
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
