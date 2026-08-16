@extends('layouts.admin', ['heading' => 'Edit Maintenance'])

@section('title', 'Edit Maintenance')

@section('content')
    <x-ui.page-header title="Edit Maintenance" :subtitle="$maintenance->asset?->name">
        <x-slot:actions>
            <a href="{{ route('admin.asset-maintenances.show', $maintenance) }}" class="btn btn-light">View Record</a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <form action="{{ route('admin.asset-maintenances.update', $maintenance) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @include('admin.asset-maintenances.partials.form', [
                'maintenance' => $maintenance,
                'assets' => collect([$maintenance->asset])->filter(),
                'selectedAssetId' => $maintenance->asset_id,
            ])

            <div class="d-flex flex-wrap gap-2">
                <x-ui.button type="submit">Save Changes</x-ui.button>
                <a href="{{ route('admin.asset-maintenances.show', $maintenance) }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
