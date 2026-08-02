@extends('layouts.admin', ['heading' => 'Profile'])

@section('title', 'Profile')

@section('content')
    <x-ui.page-header
        title="Profile"
        subtitle="Update your account information"
    />

    <x-ui.card>
        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-lg-8">
                    <x-forms.input
                        label="Full name"
                        name="name"
                        :value="$user->name"
                        required
                    />

                    <x-forms.input
                        label="Email address"
                        name="email"
                        type="email"
                        :value="$user->email"
                        required
                    />

                    <x-forms.input
                        label="Phone"
                        name="phone"
                        :value="$user->phone"
                    />
                </div>
            </div>

            <div class="d-flex gap-2">
                <x-ui.button type="submit">Save Profile</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
