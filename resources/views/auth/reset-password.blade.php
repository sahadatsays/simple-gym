@extends('layouts.auth')

@section('content')
    <div class="sg-auth-form-card">
        <div class="sg-auth-form-header">
            <h1 class="sg-auth-form-title">{{ __('auth.reset.title') }}</h1>
            <p class="sg-auth-form-subtitle">{{ __('auth.reset.subtitle') }}</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger py-2 mb-4" role="alert">
                <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('password.update') }}" method="POST" class="sg-auth-form">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

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
                        value="{{ old('email', $email) }}"
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
                    {{ __('common.fields.new_password') }}
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
                        placeholder="{{ __('common.placeholders.new_password') }}"
                        class="form-control @error('password') is-invalid @enderror"
                        required
                        autocomplete="new-password"
                    >
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-4">
                <label for="password_confirmation" class="form-label">
                    {{ __('common.fields.confirm_password') }}
                    <span class="text-danger">*</span>
                </label>
                <div class="input-group sg-auth-input-group">
                    <span class="input-group-text">
                        <i class="bi bi-shield-lock" aria-hidden="true"></i>
                    </span>
                    <input
                        type="password"
                        name="password_confirmation"
                        id="password_confirmation"
                        placeholder="{{ __('common.placeholders.confirm_new_password') }}"
                        class="form-control"
                        required
                        autocomplete="new-password"
                    >
                </div>
            </div>

            <button type="submit" class="btn sg-auth-btn w-100 mb-3">
                {{ __('auth.reset.submit') }}
            </button>

            <div class="text-center">
                <a href="{{ route('login') }}" class="sg-auth-link small">
                    {{ __('auth.forgot.back_to_sign_in') }}
                </a>
            </div>
        </form>
    </div>
@endsection
