@extends('layouts.admin', ['heading' => 'Disposal Details'])

@section('title', 'Disposal Record')

@section('content')
    <x-ui.page-header :title="$disposal->disposal_type->label()" :subtitle="$disposal->asset?->name">
        <x-slot:actions>
            <div class="d-flex flex-wrap gap-2">
                @if ($disposal->asset)
                    <a href="{{ route('admin.assets.show', $disposal->asset) }}" class="btn btn-light">View Asset</a>
                @endif
                <a href="{{ route('admin.asset-disposals.index') }}" class="btn btn-light">Back to Disposals</a>
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="row g-4">
        <div class="col-lg-6">
            <x-admin.detail-section title="Disposal Details">
                <x-admin.detail-list>
                    <x-admin.detail-item label="Asset">
                        @if ($disposal->asset)
                            <a href="{{ route('admin.assets.show', $disposal->asset) }}">
                                {{ $disposal->asset->name }}
                            </a>
                            <div class="small text-muted">{{ $disposal->asset->asset_code }}</div>
                        @else
                            —
                        @endif
                    </x-admin.detail-item>
                    <x-admin.detail-item label="Disposal date" :value="$disposal->disposed_at->format('M j, Y')" />
                    <x-admin.detail-item label="Disposal type" :value="$disposal->disposal_type->label()" />
                    <x-admin.detail-item label="Sale amount">
                        @if ($disposal->sale_amount !== null)
                            {{ App\Support\MoneyFormatter::format($disposal->sale_amount, $gymCurrency) }}
                        @else
                            —
                        @endif
                    </x-admin.detail-item>
                    <x-admin.detail-item label="Buyer" :value="$disposal->buyer ?: '—'" />
                    @if ($disposal->creator)
                        <x-admin.detail-item label="Recorded by" :value="$disposal->creator->name" />
                    @endif
                </x-admin.detail-list>
            </x-admin.detail-section>
        </div>

        <div class="col-lg-6">
            <x-admin.detail-section title="Reason & Notes">
                @if ($disposal->reason)
                    <div class="mb-3">
                        <h3 class="h6 fw-semibold mb-2">Reason</h3>
                        <p class="mb-0 text-muted">{{ $disposal->reason }}</p>
                    </div>
                @endif

                @if ($disposal->notes)
                    <div class="@if ($disposal->reason) mt-3 pt-3 border-top @endif">
                        <h3 class="h6 fw-semibold mb-2">Notes</h3>
                        <p class="mb-0 text-muted">{{ $disposal->notes }}</p>
                    </div>
                @endif

                @if (! $disposal->reason && ! $disposal->notes)
                    <p class="text-muted mb-0">No additional details provided.</p>
                @endif
            </x-admin.detail-section>
        </div>
    </div>
@endsection
