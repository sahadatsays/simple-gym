@extends('layouts.admin', ['heading' => 'Create Investment Category'])

@section('title', 'Create Investment Category')

@section('content')
    <x-ui.page-header title="Create Investment Category" subtitle="Add a category for organizing investments" />

    <x-ui.card>
        <form action="{{ route('admin.investment-categories.store') }}" method="POST">
            @csrf

            @include('admin.investment-categories.partials.form', ['category' => null])

            <div class="d-flex flex-wrap gap-2">
                <x-ui.button type="submit">Create Category</x-ui.button>
                <a href="{{ route('admin.investment-categories.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
