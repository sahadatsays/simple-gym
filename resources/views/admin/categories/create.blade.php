@extends('layouts.admin', ['heading' => 'Create Category'])

@section('title', 'Create Category')

@section('content')
    <x-ui.page-header title="Create Category" subtitle="Add a new product category" />

    <x-ui.card>
        <form action="{{ route('admin.categories.store') }}" method="POST">
            @csrf

            @include('admin.categories.partials.form', ['category' => null])

            <div class="d-flex gap-2 mt-4">
                <x-ui.button type="submit">Create Category</x-ui.button>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
