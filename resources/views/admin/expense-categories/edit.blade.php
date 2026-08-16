@extends('layouts.admin', ['heading' => 'Edit Expense Category'])

@section('title', 'Edit '.$category->name)

@section('content')
    <x-ui.page-header :title="$category->name" subtitle="Update expense category details" />

    <x-ui.card>
        <form action="{{ route('admin.expense-categories.update', $category) }}" method="POST">
            @csrf
            @method('PUT')

            @include('admin.expense-categories.partials.form', ['category' => $category])

            <div class="d-flex flex-wrap gap-2">
                <x-ui.button type="submit">Save Changes</x-ui.button>
                <a href="{{ route('admin.expense-categories.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
