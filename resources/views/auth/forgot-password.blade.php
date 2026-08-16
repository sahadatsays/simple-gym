@extends('layouts.auth')

@section('content')
    <div class="sg-auth-form-card">
        <div class="sg-auth-form-header">
            <h1 class="sg-auth-form-title">{{ __('auth.forgot.title') }}</h1>
            <p class="sg-auth-form-subtitle">{{ __('auth.forgot.subtitle') }}</p>
        </div>

        <x-ui.status />

        <form action="{{ route('password.email') }}" method="POST" class="sg-auth-form">
            @csrf

            <div class="mb-4">
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

            <button type="submit" class="btn sg-auth-btn w-100 mb-3">
                {{ __('auth.forgot.send_link') }}
            </button>

            <div class="text-center">
                <a href="{{ route('login') }}" class="sg-auth-link small">
                    {{ __('auth.forgot.back_to_sign_in') }}
                </a>
            </div>
        </form>
    </div>
@endsection
