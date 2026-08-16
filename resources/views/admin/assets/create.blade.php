@extends('layouts.admin', ['heading' => 'Create Asset'])

@section('title', 'Create Asset')

@section('content')
    <x-ui.page-header title="Create Asset" subtitle="Register a new gym asset" />

    <x-ui.card>
        <form action="{{ route('admin.assets.store') }}" method="POST">
            @csrf

            @include('admin.assets.partials.form', [
                'asset' => null,
                'categories' => $categories,
            ])

            <div class="d-flex flex-wrap gap-2">
                <x-ui.button type="submit">Create Asset</x-ui.button>
                <a href="{{ route('admin.assets.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
