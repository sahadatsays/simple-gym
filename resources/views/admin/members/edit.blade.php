@extends('layouts.admin', ['heading' => 'Edit Member'])

@section('title', 'Edit Member')

@section('content')
    <x-ui.page-header title="Edit Member" subtitle="Update member profile and membership details">
        <x-slot:actions>
            <a href="{{ route('admin.members.show', $member) }}" class="btn btn-light">View Profile</a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <form action="{{ route('admin.members.update', $member) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @include('admin.members.partials.form', ['member' => $member])

            <div class="d-flex flex-wrap gap-2 mt-4">
                <x-ui.button type="submit">Save Changes</x-ui.button>
                <a href="{{ route('admin.members.show', $member) }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
