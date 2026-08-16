@extends('layouts.admin', ['heading' => __('users.edit')])

@section('title', __('users.edit'))

@section('content')
    <x-ui.page-header :title="__('users.edit')" :subtitle="$user->name" />

    <x-ui.card class="mb-4">
        <form action="{{ route('admin.users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')

            @include('admin.users.partials.form', ['user' => $user])

            <div class="d-flex gap-2">
                <x-ui.button type="submit">{{ __('common.actions.save') }}</x-ui.button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-light">{{ __('common.actions.cancel') }}</a>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card :title="__('users.reset_password_section')">
        <form action="{{ route('admin.users.reset-password', $user) }}" method="POST" class="row g-3">
            @csrf
            @method('PUT')

            <div class="col-md-6">
                <x-forms.input :label="__('common.fields.new_password')" name="password" type="password" required />
            </div>
            <div class="col-md-6">
                <x-forms.input :label="__('common.fields.confirm_password')" name="password_confirmation" type="password" required />
            </div>
            <div class="col-12">
                <x-ui.button type="submit" variant="warning">{{ __('users.reset_password') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
