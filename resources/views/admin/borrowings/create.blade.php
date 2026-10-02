@extends('layouts.admin', ['heading' => 'Create Borrowing'])

@section('title', 'Create Borrowing')

@section('content')
    <x-ui.page-header title="Create Borrowing" subtitle="Record money the gym must return" />

    <x-ui.card>
        <form action="{{ route('admin.borrowings.store') }}" method="POST">
            @csrf

            @include('admin.borrowings.partials.form', ['borrowing' => null])

            <div class="d-flex flex-wrap gap-2">
                <x-ui.button type="submit">Create Borrowing</x-ui.button>
                <a href="{{ route('admin.borrowings.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
