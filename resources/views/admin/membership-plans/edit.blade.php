@extends('layouts.admin', ['heading' => 'Edit Membership Plan'])

@section('title', 'Edit Membership Plan')

@section('content')
    <x-ui.page-header title="Edit Membership Plan" subtitle="Update plan details and pricing" />

    <x-ui.card>
        <form action="{{ route('admin.membership-plans.update', $plan) }}" method="POST">
            @csrf
            @method('PUT')

            @include('admin.membership-plans.partials.form', ['plan' => $plan])

            <div class="d-flex gap-2">
                <x-ui.button type="submit">Save Changes</x-ui.button>
                <a href="{{ route('admin.membership-plans.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
