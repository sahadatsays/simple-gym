@extends('layouts.auth')

@section('content')
    <div class="card sg-auth-card mx-auto">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <x-auth.logo class="mx-auto mb-3" />
                <h1 class="h4 fw-bold mb-1">{{ config('gym.defaults.name') }}</h1>
                <p class="text-muted mb-0">Sign in to your admin account</p>
            </div>

            <x-ui.status />

            @if ($errors->any())
                <div class="alert alert-danger py-2" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="sg-auth-form">
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

                <x-forms.input
                    label="Password"
                    name="password"
                    type="password"
                    placeholder="Enter your password"
                    required
                    autocomplete="current-password"
                />

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <x-forms.checkbox
                        label="Remember me"
                        name="remember"
                        :checked="old('remember')"
                        class="mb-0"
                    />

                    <a href="{{ route('password.request') }}" class="sg-auth-link small">
                        Forgot password?
                    </a>
                </div>

                <button type="submit" class="btn sg-auth-btn w-100">
                    Sign in
                </button>
            </form>
        </div>
    </div>
@endsection
