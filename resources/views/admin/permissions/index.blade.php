@extends('layouts.admin', ['heading' => 'Permissions'])

@section('title', 'Permissions')

@section('content')
    <x-ui.page-header title="Permissions" subtitle="Manage application permissions stored in the database">
        <x-slot:actions>
            @can('create', Spatie\Permission\Models\Permission::class)
                <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary">Add Permission</a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    @foreach ($groupedPermissions as $group => $permissions)
        <x-ui.card class="mb-3" :title="ucfirst(str_replace(['-', '_'], ' ', $group))">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Permission</th>
                            <th>Guard</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($permissions as $permission)
                            <tr>
                                <td class="fw-semibold">{{ $permission->name }}</td>
                                <td>{{ $permission->guard_name }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
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
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endforeach
@endsection
