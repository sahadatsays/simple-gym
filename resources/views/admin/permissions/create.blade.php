@extends('layouts.admin', ['heading' => 'Create Permission'])

@section('title', 'Create Permission')

@section('content')
    <x-ui.page-header title="Create Custom Permission" subtitle="Add a permission that is not part of the system defaults" />

    <x-ui.card>
        <form action="{{ route('admin.permissions.store') }}" method="POST">
            @csrf

            <div class="row">
                <div class="col-lg-8">
                    <x-forms.input
                        label="Permission name"
                        name="name"
                        placeholder="members.archive"
                        required
                        help="Format: module.action (e.g. members.archive). System default permissions cannot be created here."
                    />
                </div>
            </div>

            <div class="d-flex gap-2">
                <x-ui.button type="submit">Create Permission</x-ui.button>
                <a href="{{ route('admin.permissions.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
