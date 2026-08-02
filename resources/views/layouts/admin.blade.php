@extends('layouts.app')

@section('title', ($title ?? 'Admin').' | '.config('app.name'))

@section('body')
    <div class="sg-admin-wrapper" x-data="{ sidebarOpen: false }">
        @include('layouts.partials.sidebar')

        <div class="sg-main">
            @include('layouts.partials.topbar')

            <main class="sg-content">
                <x-ui.flash />
                @yield('content')
            </main>
        </div>

        <div
            class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-lg-none"
            x-show="sidebarOpen"
            x-transition
            @click="sidebarOpen = false"
            style="z-index: 1025;"
        ></div>
    </div>
@endsection
