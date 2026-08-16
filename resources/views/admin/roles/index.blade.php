@extends('layouts.admin', ['heading' => __('roles.title')])

@section('title', __('roles.title'))

@section('content')
    <x-ui.page-header :title="__('roles.title')" :subtitle="__('roles.subtitle')">
        <x-slot:actions>
            @can('create', App\Models\Role::class)
                <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">{{ __('roles.add_role') }}</a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('common.table.role') }}</th>
                        <th>{{ __('common.table.slug') }}</th>
                        <th>{{ __('common.table.permissions') }}</th>
                        <th class="text-end">{{ __('common.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        @php
                            $displayNameKey = 'roles.display_names.'.str_replace('-', '_', $role->name);
                            $displayName = __($displayNameKey);
                            $displayName = $displayName !== $displayNameKey ? $displayName : $role->display_name;
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $displayName }}</td>
                            <td><code>{{ $role->name }}</code></td>
                            <td>
                                <x-ui.badge variant="secondary">
                                    {{ __('roles.permissions_assigned', ['count' => $role->permissions_count]) }}
                                </x-ui.badge>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    @can('update', $role)
                                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">
                                            {{ __('common.actions.edit') }}
                                        </a>
                                    @endcan
                                    @can('delete', $role)
                                        <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" onsubmit="return confirm(@js(__('roles.delete_role_confirm')))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                {{ __('common.actions.delete') }}
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">{{ __('roles.no_roles') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endsection
