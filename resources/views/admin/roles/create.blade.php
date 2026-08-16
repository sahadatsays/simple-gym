@extends('layouts.admin', ['heading' => __('roles.create')])

@section('title', __('roles.create'))

@section('content')
    <x-ui.page-header :title="__('roles.create')" :subtitle="__('roles.create_subtitle')" />

    <x-ui.card>
        <form action="{{ route('admin.roles.store') }}" method="POST">
            @csrf

            @include('admin.roles.partials.form', [
                'role' => null,
                'groupedPermissions' => $groupedPermissions,
                'isProtected' => false,
            ])

            <div class="d-flex gap-2">
                <x-ui.button type="submit">{{ __('roles.create') }}</x-ui.button>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-light">{{ __('common.actions.cancel') }}</a>
            </div>
        </form>
    </x-ui.card>
@endsection
