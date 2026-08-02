@extends('layouts.auth')

@section('content')
    <div class="card sg-auth-card mx-auto">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <x-auth.logo class="mx-auto mb-3" />
                <h1 class="h4 fw-bold mb-1">Forgot password</h1>
                <p class="text-muted mb-0">Enter your email to receive a reset link</p>
            </div>

            <x-ui.status />

            <form action="{{ route('password.email') }}" method="POST" class="sg-auth-form">
                @csrf

                <x-forms.input
                    label="Email address"
                    name="email"
                    type="email"
                    placeholder="you@example.com"
                    :value="old('email')"
                    required
                    autofocus
                    autocomplete="email"
                />

                <button type="submit" class="btn sg-auth-btn w-100 mb-3">
                    Send reset link
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
