@extends('layouts.admin', ['heading' => 'Edit Role'])

@section('title', 'Edit Role')

@section('content')
    <x-ui.page-header title="Edit Role" :subtitle="str_replace('-', ' ', $role->name)" />

    <x-ui.card>
        <form action="{{ route('admin.roles.update', $role) }}" method="POST">
            @csrf
            @method('PUT')

            @include('admin.roles.partials.form', [
                'role' => $role,
                'groupedPermissions' => $groupedPermissions,
                'isProtected' => $isProtected,
            ])

            <div class="d-flex gap-2">
                <x-ui.button type="submit">Save Role</x-ui.button>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
