@extends('layouts.admin', ['heading' => 'Dispose Asset'])

@section('title', 'Dispose Asset')

@section('content')
    <x-ui.page-header title="Dispose Asset" subtitle="Select an asset and enter disposal details" />

    <x-ui.card>
        <form action="{{ route('admin.asset-disposals.confirm') }}" method="POST">
            @csrf

            @include('admin.asset-disposals.partials.form', [
                'disposal' => null,
                'assets' => $assets,
                'selectedAssetId' => $selectedAssetId,
            ])

            <div class="d-flex flex-wrap gap-2">
                <x-ui.button type="submit">Review & Confirm</x-ui.button>
                <a href="{{ route('admin.asset-disposals.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection
