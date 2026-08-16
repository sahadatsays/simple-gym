@extends('layouts.admin', ['heading' => 'Create Investment'])

@section('title', 'Create Investment')

@section('content')
    <x-ui.page-header title="Create Investment" subtitle="Record a new investment" />

    <x-ui.card>
        <form action="{{ route('admin.investments.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            @include('admin.investments.partials.form', [
                'investment' => null,
                'categories' => $categories,
            ])

            <div class="d-flex flex-wrap gap-2">
                <x-ui.button type="submit">Create Investment</x-ui.button>
                <a href="{{ route('admin.investments.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
