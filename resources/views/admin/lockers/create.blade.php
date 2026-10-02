@extends('layouts.admin', ['heading' => 'Add Locker'])

@section('title', 'Add Locker')

@section('content')
    <x-ui.page-header title="Add Locker" subtitle="Register a locker and its monthly fee" />

    <x-ui.card>
        <form action="{{ route('admin.lockers.store') }}" method="POST">
            @csrf
            <x-forms.error-summary />

            @include('admin.lockers.partials.form', ['locker' => null])

            <div class="d-flex flex-wrap gap-2">
                <x-ui.button type="submit">Create Locker</x-ui.button>
                <a href="{{ route('admin.lockers.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
