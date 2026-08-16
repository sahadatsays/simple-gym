@extends('layouts.admin', ['heading' => 'Create Expense'])

@section('title', 'Create Expense')

@section('content')
    <x-ui.page-header title="Create Expense" subtitle="Record a new operating expense" />

    <x-ui.card>
        <form action="{{ route('admin.expenses.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            @include('admin.expenses.partials.form', ['expense' => null, 'categories' => $categories])

            <div class="d-flex flex-wrap gap-2">
                <x-ui.button type="submit">Create Expense</x-ui.button>
                <a href="{{ route('admin.expenses.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
