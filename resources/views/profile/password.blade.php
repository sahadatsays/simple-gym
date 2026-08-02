@extends('layouts.admin', ['heading' => 'Change Password'])

@section('title', 'Change Password')

@section('content')
    <x-ui.page-header
        title="Change Password"
        subtitle="Update your password to keep your account secure"
    />

    <x-ui.card>
        <form action="{{ route('profile.password.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-lg-8">
                    <x-forms.input
                        label="Current password"
                        name="current_password"
                        type="password"
                        required
                        autofocus
                    />

                    <x-forms.input
                        label="New password"
                        name="password"
                        type="password"
                        required
                    />

                    <x-forms.input
                        label="Confirm new password"
                        name="password_confirmation"
                        type="password"
                        required
                    />
                </div>
            </div>

            <div class="d-flex gap-2">
                <x-ui.button type="submit">Update Password</x-ui.button>
                <a href="{{ route('profile.edit') }}" class="btn btn-light">Back to Profile</a>
            </div>
        </form>
    </x-ui.card>
@endsection
