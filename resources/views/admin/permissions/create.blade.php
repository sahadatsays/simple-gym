@extends('layouts.admin', ['heading' => __('roles.permissions.create')])

@section('title', __('roles.permissions.create'))

@section('content')
    <x-ui.page-header :title="__('roles.permissions.create')" :subtitle="__('roles.permissions.create_subtitle')" />

    <x-ui.card>
        <form action="{{ route('admin.permissions.store') }}" method="POST">
            @csrf

            <div class="row">
                <div class="col-lg-8">
                    <x-forms.input
                        :label="__('roles.permissions.permission_name')"
                        name="name"
                        :placeholder="__('roles.permissions.permission_name_placeholder')"
                        required
                        :help="__('roles.permissions.permission_name_help')"
                    />
                </div>
            </div>

            <div class="d-flex gap-2">
                <x-ui.button type="submit">{{ __('roles.permissions.create_permission') }}</x-ui.button>
                <a href="{{ route('admin.permissions.index') }}" class="btn btn-light">{{ __('common.actions.cancel') }}</a>
            </div>
        </form>
    </x-ui.card>
@endsection
