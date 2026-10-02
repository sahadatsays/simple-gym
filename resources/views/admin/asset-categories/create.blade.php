@extends('layouts.admin', ['heading' => 'Create Asset Category'])

@section('title', 'Create Asset Category')

@section('content')
    <x-ui.page-header title="Create Asset Category" subtitle="Add a category for grouping gym equipment" />

    <x-ui.card>
        <form action="{{ route('admin.asset-categories.store') }}" method="POST">
            @csrf

            @include('admin.asset-categories.partials.form', ['category' => null])

            <div class="d-flex flex-wrap gap-2">
                <x-ui.button type="submit">Create Category</x-ui.button>
                <a href="{{ route('admin.asset-categories.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
