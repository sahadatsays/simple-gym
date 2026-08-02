@extends('layouts.admin', ['heading' => 'Users'])

@section('title', 'Users')

@section('content')
    <x-ui.page-header title="Users" subtitle="Manage staff and admin accounts">
        <x-slot:actions>
            @can('create', App\Models\User::class)
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-1" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
                    </svg>
                    Add User
                </a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-admin.filter-bar class="mb-4">
        <form action="{{ route('admin.users.index') }}" method="GET" class="sg-filter-grid">
            <x-admin.filter-field label="Search" for="search">
                <div class="input-group sg-filter-input-group">
                    <input
                        type="search"
                        name="search"
                        id="search"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Name, username, email, phone..."
                        class="form-control ps-2"
                    >
                </div>
            </x-admin.filter-field>

            <x-admin.filter-field label="Role" for="role">
                <select name="role" id="role" class="form-select">
                    <option value="">All roles</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>
                            {{ ucwords(str_replace('-', ' ', $role)) }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Status" for="status">
                <select name="status" id="status" class="form-select">
                    <option value="">All statuses</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Actions" class="sg-filter-actions-field">
                <div class="sg-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-light">Reset</a>
                </div>
            </x-admin.filter-field>
        </form>
    </x-admin.filter-bar>

    <div class="card border-0 shadow-sm sg-data-table-card">
        <div class="card-body p-0">
            <div class="table-responsive sg-data-table-wrapper">
                <table class="table table-hover align-middle mb-0 sg-data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td class="ps-4">
                                    <x-admin.user-cell :user="$user" />
                                </td>
                                <td class="text-muted">{{ $user->email }}</td>
                                <td class="text-muted">{{ $user->phone ?? '—' }}</td>
                                <td>
                                    @foreach ($user->roles as $role)
                                        <span class="sg-role-badge">{{ str_replace('-', ' ', $role->name) }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    @if ($user->is_active)
                                        <span class="sg-status-badge sg-status-badge-active">Active</span>
                                    @else
                                        <span class="sg-status-badge sg-status-badge-inactive">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <x-admin.user-actions :user="$user" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="sg-empty-state">
                                        <div class="sg-empty-state-icon mb-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                                <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1z"/>
                                            </svg>
                                        </div>
                                        <h3 class="h6 mb-1">No users found</h3>
                                        <p class="text-muted small mb-0">Try adjusting your search or filters.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($users->hasPages())
            <div class="card-footer bg-white border-top-0 px-4 py-3">
                {{ $users->withQueryString()->links() }}
            </div>
        @endif
    </div>

    @foreach ($users as $user)
        @can('update', $user)
            <div class="modal fade" id="resetPasswordModal-{{ $user->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <form action="{{ route('admin.users.reset-password', $user) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title fw-bold">Reset Password</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted small mb-3">Set a new password for <strong>{{ $user->name }}</strong>.</p>
                                <x-forms.input label="New password" name="password" type="password" required />
                                <x-forms.input label="Confirm password" name="password_confirmation" type="password" required />
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Reset Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    @endforeach
@endsection
