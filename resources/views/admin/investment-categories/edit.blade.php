@extends('layouts.admin', ['heading' => 'Edit Investment Category'])

@section('title', 'Edit '.$category->name)

@section('content')
    <x-ui.page-header :title="$category->name" subtitle="Update investment category details" />

    <x-ui.card>
        <form action="{{ route('admin.investment-categories.update', $category) }}" method="POST">
            @csrf
            @method('PUT')

            @include('admin.investment-categories.partials.form', ['category' => $category])

            <div class="d-flex flex-wrap gap-2">
                <x-ui.button type="submit">Save Changes</x-ui.button>
                <a href="{{ route('admin.investment-categories.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
