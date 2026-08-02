@extends('layouts.admin', ['heading' => 'Create Role'])

@section('title', 'Create Role')

@section('content')
    <x-ui.page-header title="Create Role" subtitle="Define a role and assign permissions" />

    <x-ui.card>
        <form action="{{ route('admin.roles.store') }}" method="POST">
            @csrf

            @include('admin.roles.partials.form', [
                'role' => null,
                'groupedPermissions' => $groupedPermissions,
                'isProtected' => false,
            ])

            <div class="d-flex gap-2">
                <x-ui.button type="submit">Create Role</x-ui.button>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
