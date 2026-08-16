@extends('layouts.admin', ['heading' => __('roles.permissions.edit')])

@section('title', __('roles.permissions.edit'))

@section('content')
    <x-ui.page-header :title="__('roles.permissions.edit')" :subtitle="$permission->name" />

    <x-ui.card>
        <form action="{{ route('admin.permissions.update', $permission) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-lg-8">
                    <x-forms.input
                        :label="__('roles.permissions.permission_name')"
                        name="name"
                        :value="$permission->name"
                        required
                        :help="__('roles.permissions.permission_name_edit_help')"
                    />
                </div>
            </div>

            <div class="d-flex gap-2">
                <x-ui.button type="submit">{{ __('roles.permissions.save_permission') }}</x-ui.button>
                <a href="{{ route('admin.permissions.index') }}" class="btn btn-light">{{ __('common.actions.cancel') }}</a>
            </div>
        </form>
    </x-ui.card>
@endsection
