@extends('layouts.app')

@section('title', 'Login | '.config('app.name'))

@section('body')
    <div class="sg-auth-wrapper">
        <div class="container px-3">
            <x-ui.flash />
            @yield('content')
        </div>
    </div>
@endsection
