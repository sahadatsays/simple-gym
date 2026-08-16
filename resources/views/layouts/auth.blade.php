@extends('layouts.app')

@section('title', 'Login | '.config('app.name'))

@section('body')
    <div class="sg-auth-wrapper">
        <div class="container px-3 position-relative">
            <div class="d-flex justify-content-end pt-3">
                <x-ui.locale-switcher />
            </div>

            <x-ui.flash />
            @yield('content')
        </div>
    </div>
@endsection
