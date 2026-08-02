@extends('layouts.admin', ['heading' => 'Roles'])

@section('title', 'Roles')

@section('content')
    <x-ui.page-header title="Roles" subtitle="Manage roles and assign permissions dynamically">
        <x-slot:actions>
            @can('create', Spatie\Permission\Models\Role::class)
                <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">Add Role</a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Permissions</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr>
                            <td>
                                <div class="fw-semibold text-capitalize">{{ str_replace('-', ' ', $role->name) }}</div>
                                <div class="small text-muted">{{ $role->name }}</div>
                            </td>
                            <td>
                                <x-ui.badge variant="secondary">{{ $role->permissions_count }} assigned</x-ui.badge>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    @can('update', $role)
                                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    @endcan
                                    @can('delete', $role)
                                        <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" onsubmit="return confirm('Delete this role?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">No roles found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endsection
