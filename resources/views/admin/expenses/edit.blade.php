@extends('layouts.admin', ['heading' => 'Edit Expense'])

@section('title', 'Edit '.$expense->expense_number)

@section('content')
    <x-ui.page-header :title="$expense->expense_number" subtitle="Update expense details">
        <x-slot:actions>
            <a href="{{ route('admin.expenses.show', $expense) }}" class="btn btn-light">View Expense</a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <form action="{{ route('admin.expenses.update', $expense) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @include('admin.expenses.partials.form', ['expense' => $expense, 'categories' => $categories])

            <div class="d-flex flex-wrap gap-2">
                <x-ui.button type="submit">Save Changes</x-ui.button>
                <a href="{{ route('admin.expenses.show', $expense) }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
