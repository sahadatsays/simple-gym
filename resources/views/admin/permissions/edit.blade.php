@extends('layouts.admin', ['heading' => 'Edit Permission'])

@section('title', 'Edit Permission')

@section('content')
    <x-ui.page-header title="Edit Permission" :subtitle="$permission->name" />

    <x-ui.card>
        <form action="{{ route('admin.permissions.update', $permission) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-lg-8">
                    <x-forms.input
                        label="Permission name"
                        name="name"
                        :value="$permission->name"
                        required
                        help="Format: module.action (e.g. members.view, payments.create)"
                    />
                </div>
            </div>

            <div class="d-flex gap-2">
                <x-ui.button type="submit">Save Permission</x-ui.button>
                <a href="{{ route('admin.permissions.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
