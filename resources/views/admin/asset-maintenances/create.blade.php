@extends('layouts.admin', ['heading' => 'Record Maintenance'])

@section('title', 'Record Maintenance')

@section('content')
    <x-ui.page-header title="Record Maintenance" subtitle="Select an asset and enter maintenance details" />

    <x-ui.card>
        <form action="{{ route('admin.asset-maintenances.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            @include('admin.asset-maintenances.partials.form', [
                'maintenance' => null,
                'assets' => $assets,
                'selectedAssetId' => $selectedAssetId,
            ])

            <div class="d-flex flex-wrap gap-2">
                <x-ui.button type="submit">Save Maintenance</x-ui.button>
                <a href="{{ route('admin.asset-maintenances.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
