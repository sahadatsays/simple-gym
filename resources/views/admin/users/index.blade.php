@extends('layouts.admin', ['heading' => 'Users'])

@section('title', 'Users')

@section('content')
    <x-ui.page-header title="Users" subtitle="Manage staff and admin accounts">
        <x-slot:actions>
            @can('create', App\Models\User::class)
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Add User</a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @foreach ($user->roles as $role)
                                    <x-ui.badge>{{ $role->name }}</x-ui.badge>
                                @endforeach
                            </td>
                            <td>
                                @if ($user->is_active)
                                    <x-ui.badge variant="success">Active</x-ui.badge>
                                @else
                                    <x-ui.badge variant="danger">Inactive</x-ui.badge>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    @can('update', $user)
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    @endcan

                                    @can('delete', $user)
                                        <form
                                            action="{{ route('admin.users.destroy', $user) }}"
                                            method="POST"
                                            onsubmit="return confirm('Delete this user?')"
                                        >
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
                            <td colspan="5" class="text-center text-muted py-4">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $users->links() }}
        </div>
    </x-ui.card>
@endsection
