@extends('layouts.admin', ['heading' => 'Create User'])

@section('title', 'Create User')

@section('content')
    <x-ui.page-header title="Create User" subtitle="Add a new admin or staff account" />

    <x-ui.card>
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf

            @include('admin.users.partials.form', ['user' => null])

            <div class="d-flex gap-2">
                <x-ui.button type="submit">Create User</x-ui.button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
