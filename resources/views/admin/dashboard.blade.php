@extends('layouts.admin', ['heading' => 'Dashboard'])

@section('title', 'Dashboard')

@section('content')
    <x-ui.page-header
        title="Dashboard"
        subtitle="Overview of your gym management system"
    />

    <div class="row g-4">
        <div class="col-md-6 col-xl-4">
            <x-ui.card class="sg-stat-card">
                <p class="text-muted mb-1">Total Users</p>
                <h2 class="h3 mb-0">{{ $stats['users'] }}</h2>
            </x-ui.card>
        </div>

        <div class="col-md-6 col-xl-4">
            <x-ui.card class="sg-stat-card">
                <p class="text-muted mb-1">Active Users</p>
                <h2 class="h3 mb-0">{{ $stats['active_users'] }}</h2>
            </x-ui.card>
        </div>
    </div>
@endsection
