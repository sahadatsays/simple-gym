@extends('layouts.admin', ['heading' => 'Edit Borrowing'])

@section('title', 'Edit '.$borrowing->borrowing_no)

@section('content')
    <x-ui.page-header :title="$borrowing->borrowing_no" subtitle="Update borrowing details">
        <x-slot:actions>
            <a href="{{ route('admin.borrowings.show', $borrowing) }}" class="btn btn-light">View Borrowing</a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <form action="{{ route('admin.borrowings.update', $borrowing) }}" method="POST">
            @csrf
            @method('PUT')

            @include('admin.borrowings.partials.form', ['borrowing' => $borrowing])

            <div class="d-flex flex-wrap gap-2">
                <x-ui.button type="submit">Save Changes</x-ui.button>
                <a href="{{ route('admin.borrowings.show', $borrowing) }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
