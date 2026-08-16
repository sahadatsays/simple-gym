@extends('layouts.admin', ['heading' => 'Permissions'])

@section('title', 'Permissions')

@section('content')
    <x-ui.page-header title="Permissions" subtitle="System default permissions are managed by the application. Custom permissions can be edited or removed.">
        <x-slot:actions>
            @can('create', Spatie\Permission\Models\Permission::class)
                <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary">Add Custom Permission</a>
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
                                                <span class="badge text-bg-secondary">System</span>
                                            @else
                                                <span class="badge text-bg-primary">Custom</span>
                                            @endif
                                        </div>
                                        <div class="sg-permission-item__slug">{{ $permission->name }}</div>
                                        <div class="small text-muted mt-1">Guard: {{ $permission->guard_name }}</div>
                                    </div>

                                    @unless ($isDefault)
                                        <div class="sg-permission-card__actions">
                                            @can('update', $permission)
                                                <a href="{{ route('admin.permissions.edit', $permission) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                            @endcan
                                            @can('delete', $permission)
                                                <form action="{{ route('admin.permissions.destroy', $permission) }}" method="POST" onsubmit="return confirm('Delete this permission?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
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
