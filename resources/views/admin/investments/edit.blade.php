@extends('layouts.admin', ['heading' => 'Edit Investment'])

@section('title', 'Edit '.$investment->investment_number)

@section('content')
    <x-ui.page-header :title="$investment->investment_number" subtitle="Update investment details">
        <x-slot:actions>
            <a href="{{ route('admin.investments.show', $investment) }}" class="btn btn-light">View Investment</a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <form action="{{ route('admin.investments.update', $investment) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @include('admin.investments.partials.form', [
                'investment' => $investment,
                'categories' => $categories,
            ])

            <div class="d-flex flex-wrap gap-2">
                <x-ui.button type="submit">Save Changes</x-ui.button>
                <a href="{{ route('admin.investments.show', $investment) }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
