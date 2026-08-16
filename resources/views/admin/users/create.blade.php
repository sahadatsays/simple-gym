@extends('layouts.admin', ['heading' => __('users.create')])

@section('title', __('users.create'))

@section('content')
    <x-ui.page-header :title="__('users.create')" :subtitle="__('users.create_subtitle')" />

    <x-ui.card>
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf

            @include('admin.users.partials.form', ['user' => null])

            <div class="d-flex gap-2">
                <x-ui.button type="submit">{{ __('users.create') }}</x-ui.button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-light">{{ __('common.actions.cancel') }}</a>
            </div>
        </form>
    </x-ui.card>
@endsection
