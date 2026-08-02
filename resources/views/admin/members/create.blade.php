@extends('layouts.admin', ['heading' => 'Create Member'])

@section('title', 'Create Member')

@section('content')
    <x-ui.page-header title="Create Member" subtitle="Register a new gym member" />

    <x-ui.card>
        <form action="{{ route('admin.members.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            @include('admin.members.partials.form', ['member' => null])

            <div class="d-flex flex-wrap gap-2 mt-4">
                <x-ui.button type="submit">Create Member</x-ui.button>
                <a href="{{ route('admin.members.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
