@extends('layouts.admin', ['heading' => 'Edit Category'])

@section('title', 'Edit Category')

@section('content')
    <x-ui.page-header :title="$category->name" subtitle="Update category details" />

    <x-ui.card>
        <form action="{{ route('admin.categories.update', $category) }}" method="POST">
            @csrf
            @method('PUT')

            @include('admin.categories.partials.form', ['category' => $category])

            <div class="d-flex gap-2 mt-4">
                <x-ui.button type="submit">Save Changes</x-ui.button>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
