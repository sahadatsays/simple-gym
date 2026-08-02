@extends('layouts.admin', ['heading' => 'Edit User'])

@section('title', 'Edit User')

@section('content')
    <x-ui.page-header title="Edit User" :subtitle="$user->name" />

    <x-ui.card class="mb-4">
        <form action="{{ route('admin.users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')

            @include('admin.users.partials.form', ['user' => $user])

            <div class="d-flex gap-2">
                <x-ui.button type="submit">Save Changes</x-ui.button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card title="Reset Password">
        <form action="{{ route('admin.users.reset-password', $user) }}" method="POST" class="row g-3">
            @csrf
            @method('PUT')

            <div class="col-md-6">
                <x-forms.input label="New password" name="password" type="password" required />
            </div>
            <div class="col-md-6">
                <x-forms.input label="Confirm password" name="password_confirmation" type="password" required />
            </div>
            <div class="col-12">
                <x-ui.button type="submit" variant="warning">Reset Password</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
