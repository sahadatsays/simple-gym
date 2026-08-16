@extends('layouts.admin', ['heading' => __('roles.edit')])

@section('title', __('roles.edit'))

@section('content')
    @php
        $displayNameKey = 'roles.display_names.'.str_replace('-', '_', $role->name);
        $displayName = __($displayNameKey);
        $displayName = $displayName !== $displayNameKey ? $displayName : $role->display_name;
    @endphp

    <x-ui.page-header :title="__('roles.edit')" :subtitle="$displayName" />

    <x-ui.card>
        <form action="{{ route('admin.roles.update', $role) }}" method="POST">
            @csrf
            @method('PUT')

            @include('admin.roles.partials.form', [
                'role' => $role,
                'groupedPermissions' => $groupedPermissions,
                'isProtected' => $isProtected,
            ])

            <div class="d-flex gap-2">
                <x-ui.button type="submit">{{ __('roles.save_role') }}</x-ui.button>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-light">{{ __('common.actions.cancel') }}</a>
            </div>
        </form>
    </x-ui.card>
@endsection
