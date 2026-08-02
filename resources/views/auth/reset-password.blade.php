@extends('layouts.auth')

@section('content')
    <div class="card sg-auth-card mx-auto">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <x-auth.logo class="mx-auto mb-3" />
                <h1 class="h4 fw-bold mb-1">Reset password</h1>
                <p class="text-muted mb-0">Choose a new password for your account</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger py-2" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('password.update') }}" method="POST" class="sg-auth-form">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <x-forms.input
                    label="Email address"
                    name="email"
                    type="email"
                    placeholder="you@example.com"
                    :value="old('email', $email)"
                    required
                    autofocus
                    autocomplete="email"
                />

                <x-forms.input
                    label="New password"
                    name="password"
                    type="password"
                    placeholder="Create a new password"
                    required
                    autocomplete="new-password"
                />

                <x-forms.input
                    label="Confirm password"
                    name="password_confirmation"
                    type="password"
                    placeholder="Confirm your new password"
                    required
                    autocomplete="new-password"
                />

                <button type="submit" class="btn sg-auth-btn w-100 mb-3">
                    Reset password
                </button>

                <div class="text-center">
                    <a href="{{ route('login') }}" class="sg-auth-link small">
                        Back to sign in
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
