@extends('layouts.admin', ['heading' => 'Edit User'])

@section('title', 'Edit User')

@section('content')
    <x-ui.page-header title="Edit User" :subtitle="$user->name" />

    <x-ui.card>
        <form action="{{ route('admin.users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')

            @include('admin.users.partials.form', ['user' => $user])

            <div class="d-flex gap-2">
                <x-ui.button type="submit">Save Changes</x-ui.button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
