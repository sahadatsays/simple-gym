@extends('layouts.admin', ['heading' => 'Edit Locker'])

@section('title', 'Edit '.$locker->locker_number)

@section('content')
    <x-ui.page-header :title="'Edit '.$locker->locker_number" subtitle="Update locker details" />

    <x-ui.card>
        <form action="{{ route('admin.lockers.update', $locker) }}" method="POST">
            @csrf
            @method('PUT')
            <x-forms.error-summary />

            @include('admin.lockers.partials.form', ['locker' => $locker])

            <div class="d-flex flex-wrap gap-2">
                <x-ui.button type="submit">Save Locker</x-ui.button>
                <a href="{{ route('admin.lockers.show', $locker) }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
