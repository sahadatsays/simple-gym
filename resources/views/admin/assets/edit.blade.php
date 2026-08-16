@extends('layouts.admin', ['heading' => 'Edit Asset'])

@section('title', 'Edit '.$asset->name)

@section('content')
    <x-ui.page-header :title="$asset->name" subtitle="Update asset details">
        <x-slot:actions>
            <a href="{{ route('admin.assets.show', $asset) }}" class="btn btn-light">View Asset</a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <form action="{{ route('admin.assets.update', $asset) }}" method="POST">
            @csrf
            @method('PUT')

            @include('admin.assets.partials.form', [
                'asset' => $asset,
                'categories' => $categories,
            ])

            <div class="d-flex flex-wrap gap-2">
                <x-ui.button type="submit">Save Changes</x-ui.button>
                <a href="{{ route('admin.assets.show', $asset) }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
