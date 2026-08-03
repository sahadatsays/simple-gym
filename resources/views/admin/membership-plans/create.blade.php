@extends('layouts.admin', ['heading' => 'Create Membership Plan'])

@section('title', 'Create Membership Plan')

@section('content')
    <x-ui.page-header title="Create Membership Plan" subtitle="Add a new membership package" />

    <x-ui.card>
        <form action="{{ route('admin.membership-plans.store') }}" method="POST">
            @csrf

            @include('admin.membership-plans.partials.form', [
                'plan' => null,
                'defaultAdmissionFee' => $defaultAdmissionFee ?? 0,
            ])

            <div class="d-flex gap-2">
                <x-ui.button type="submit">Create Plan</x-ui.button>
                <a href="{{ route('admin.membership-plans.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
