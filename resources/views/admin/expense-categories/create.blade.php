@extends('layouts.admin', ['heading' => 'Create Expense Category'])

@section('title', 'Create Expense Category')

@section('content')
    <x-ui.page-header title="Create Expense Category" subtitle="Add a category for organizing expenses" />

    <x-ui.card>
        <form action="{{ route('admin.expense-categories.store') }}" method="POST">
            @csrf

            @include('admin.expense-categories.partials.form', ['category' => null])

            <div class="d-flex flex-wrap gap-2">
                <x-ui.button type="submit">Create Category</x-ui.button>
                <a href="{{ route('admin.expense-categories.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
