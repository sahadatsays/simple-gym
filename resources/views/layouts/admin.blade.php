@extends('layouts.app')

@section('title', ($title ?? 'Admin').' | '.config('app.name'))

@section('body')
    <div
        class="sg-admin-wrapper"
        x-data="{
            sidebarOpen: false,
            sidebarCollapsed: localStorage.getItem('sgSidebarCollapsed') === '1',
        }"
        x-init="$watch('sidebarCollapsed', value => localStorage.setItem('sgSidebarCollapsed', value ? '1' : '0'))"
        :class="{ 'sg-sidebar-is-collapsed': sidebarCollapsed }"
    >
        @include('layouts.partials.sidebar')

        <div class="sg-main">
            @include('layouts.partials.topbar')

            <main class="sg-content">
                <x-ui.flash />
                @yield('content')
            </main>
        </div>

        <div
            class="sg-sidebar-backdrop d-lg-none"
            x-show="sidebarOpen"
            x-transition.opacity
            @click="sidebarOpen = false"
            aria-hidden="true"
        ></div>
    </div>
@endsection
