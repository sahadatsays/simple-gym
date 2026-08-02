@extends('layouts.auth')

@section('content')
    <div class="card sg-auth-card mx-auto">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <h1 class="h4 mb-1">{{ config('gym.defaults.name') }}</h1>
                <p class="text-muted mb-0">Sign in to your admin account</p>
            </div>

            <form action="{{ route('login') }}" method="POST">
                @csrf

                <x-forms.input
                    label="Email address"
                    name="email"
                    type="email"
                    :value="old('email')"
                    required
                    autofocus
                />

                <x-forms.input
                    label="Password"
                    name="password"
                    type="password"
                    required
                />

                <x-forms.checkbox
                    label="Remember me"
                    name="remember"
                    :checked="old('remember')"
                />

                <x-ui.button type="submit" class="w-100">
                    Sign in
                </x-ui.button>
            </form>
        </div>
    </div>
@endsection
