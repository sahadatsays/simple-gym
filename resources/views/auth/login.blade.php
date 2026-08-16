@extends('layouts.auth')

@section('content')
    <div class="sg-auth-form-card">
        <div class="sg-auth-form-header">
            <h1 class="sg-auth-form-title">{{ __('auth.login.welcome') }}</h1>
            <p class="sg-auth-form-subtitle">{{ __('auth.login.title') }}</p>
        </div>

        <x-ui.status />

        @if ($errors->any())
            <div class="alert alert-danger py-2 mb-4" role="alert">
                <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="sg-auth-form">
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label">
                    {{ __('common.fields.email_address') }}
                    <span class="text-danger">*</span>
                </label>
                <div class="input-group sg-auth-input-group">
                    <span class="input-group-text">
                        <i class="bi bi-envelope" aria-hidden="true"></i>
                    </span>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        value="{{ old('email') }}"
                        placeholder="{{ __('common.placeholders.email') }}"
                        class="form-control @error('email') is-invalid @enderror"
                        required
                        autofocus
                        autocomplete="email"
                    >
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">
                    {{ __('common.fields.password') }}
                    <span class="text-danger">*</span>
                </label>
                <div class="input-group sg-auth-input-group">
                    <span class="input-group-text">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                    </span>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        placeholder="{{ __('common.placeholders.password') }}"
                        class="form-control @error('password') is-invalid @enderror"
                        required
                        autocomplete="current-password"
                    >
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <x-forms.checkbox
                    :label="__('auth.login.remember_me')"
                    name="remember"
                    :checked="old('remember')"
                    class="mb-0"
                />

                <a href="{{ route('password.request') }}" class="sg-auth-link small">
                    {{ __('auth.login.forgot_password') }}
                </a>
            </div>

            <button type="submit" class="btn sg-auth-btn w-100">
                <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>
                {{ __('auth.login.sign_in') }}
            </button>
        </form>

        @if ($devLogin)
            <div class="sg-dev-login mt-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <hr class="flex-grow-1 m-0">
                    <span class="small text-muted text-uppercase fw-semibold">{{ __('auth.login.dev_only') }}</span>
                    <hr class="flex-grow-1 m-0">
                </div>

                <form action="{{ route('login.dev') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn sg-dev-login-btn w-100">
                        <span class="sg-dev-login-badge">DEV</span>
                        {{ __('auth.login.dev_login', ['name' => $devLogin['name']]) }}
                    </button>
                    <p class="small text-muted text-center mb-0 mt-2">{{ $devLogin['email'] }}</p>
                </form>
            </div>
        @endif
    </div>
@endsection
