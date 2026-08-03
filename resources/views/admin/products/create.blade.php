@extends('layouts.admin', ['heading' => 'Create Product'])

@section('title', 'Create Product')

@section('content')
    <x-ui.page-header title="Create Product" subtitle="Add a new product to inventory" />

    <x-ui.card>
        <form action="{{ route('admin.products.store') }}" method="POST">
            @csrf

            @include('admin.products.partials.form', ['product' => null, 'categories' => $categories])

            <div class="d-flex gap-2 mt-4">
                <x-ui.button type="submit">Create Product</x-ui.button>
                <a href="{{ route('admin.products.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
