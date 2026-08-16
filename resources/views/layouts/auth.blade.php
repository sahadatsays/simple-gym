@extends('layouts.app')

@section('title', 'Login | '.config('app.name'))

@section('body')
    <div class="sg-auth-page">
        <x-auth.banner />

        <main class="sg-auth-panel">
            <div class="sg-auth-panel__toolbar">
                <x-ui.locale-switcher />
            </div>

            <div class="sg-auth-panel__body">
                <x-ui.flash />
                @yield('content')
            </div>
        </main>
    </div>
@endsection
