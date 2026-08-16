@php
    $disposalType = App\Enums\AssetDisposalType::tryFrom($data['disposal_type'] ?? '');
@endphp

@extends('layouts.admin', ['heading' => 'Confirm Disposal'])

@section('title', 'Confirm Disposal')

@section('content')
    <x-ui.page-header title="Confirm Asset Disposal" subtitle="Review the details before finalizing this action">
        <x-slot:actions>
            <a href="{{ route('admin.asset-disposals.create', ['asset_id' => $asset->id]) }}" class="btn btn-light">
                Edit Details
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="alert alert-warning border-0 shadow-sm mb-4">
        <strong>This action is permanent.</strong>
        The asset will not be deleted, but its status will be updated and it cannot be disposed again.
    </div>

    <x-admin.detail-section title="Disposal Summary" class="mb-4">
        <x-admin.detail-list>
            <x-admin.detail-item label="Asset" :value="$asset->name . ' (' . $asset->asset_code . ')'" />
            <x-admin.detail-item label="Disposal date" :value="\Illuminate\Support\Carbon::parse($data['disposed_at'])->format('M j, Y')" />
            <x-admin.detail-item label="Disposal type" :value="$disposalType?->label() ?? '—'" />
            <x-admin.detail-item label="Sale amount">
                @if (filled($data['sale_amount'] ?? null))
                    {{ App\Support\MoneyFormatter::format($data['sale_amount'], $gymCurrency) }}
                @else
                    —
                @endif
            </x-admin.detail-item>
            <x-admin.detail-item label="Buyer" :value="$data['buyer'] ?? '—'" />
            <x-admin.detail-item label="Reason" :value="$data['reason'] ?? '—'" />
            <x-admin.detail-item label="Notes" :value="$data['notes'] ?? '—'" />
            <x-admin.detail-item
                label="New asset status"
                :value="$disposalType?->toAssetStatus()->label() ?? '—'"
            />
        </x-admin.detail-list>
    </x-admin.detail-section>

    <x-ui.card>
        <form action="{{ route('admin.asset-disposals.store') }}" method="POST">
            @csrf

            @foreach ($data as $key => $value)
                @if (filled($value))
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach

            <div class="d-flex flex-wrap gap-2">
                <x-ui.button type="submit" variant="danger">Confirm Disposal</x-ui.button>
                <a href="{{ route('admin.asset-disposals.create', ['asset_id' => $asset->id]) }}" class="btn btn-light">Back</a>
            </div>
        </form>
    </x-ui.card>
@endsection
