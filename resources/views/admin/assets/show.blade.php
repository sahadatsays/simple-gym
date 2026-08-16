@extends('layouts.admin', ['heading' => 'Asset Details'])

@section('title', $asset->asset_code)

@section('content')
    <x-ui.page-header :title="$asset->name" :subtitle="$asset->asset_code">
        <x-slot:actions>
            @can('update', $asset)
                <a href="{{ route('admin.assets.edit', $asset) }}" class="btn btn-primary">Edit</a>
            @endcan
            <a href="{{ route('admin.assets.index') }}" class="btn btn-light">Back to Assets</a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-semibold mb-3">Asset Details</h2>
                    <dl class="row sg-profile-list mb-0">
                        <dt class="col-sm-5">Asset code</dt>
                        <dd class="col-sm-7">{{ $asset->asset_code }}</dd>

                        <dt class="col-sm-5">Category</dt>
                        <dd class="col-sm-7">{{ $asset->category?->name ?? '—' }}</dd>

                        <dt class="col-sm-5">Purchase date</dt>
                        <dd class="col-sm-7">{{ $asset->purchased_at->format('M j, Y') }}</dd>

                        <dt class="col-sm-5">Purchase price</dt>
                        <dd class="col-sm-7">{{ App\Support\MoneyFormatter::format($asset->purchase_price, $gymCurrency) }}</dd>

                        <dt class="col-sm-5">Current value</dt>
                        <dd class="col-sm-7">{{ App\Support\MoneyFormatter::format($asset->current_value ?? 0, $gymCurrency) }}</dd>

                        <dt class="col-sm-5">Supplier</dt>
                        <dd class="col-sm-7">{{ $asset->supplier ?: '—' }}</dd>

                        <dt class="col-sm-5">Location</dt>
                        <dd class="col-sm-7">{{ $asset->location ?: '—' }}</dd>

                        <dt class="col-sm-5">Condition</dt>
                        <dd class="col-sm-7">{{ $asset->condition?->label() ?? '—' }}</dd>

                        <dt class="col-sm-5">Status</dt>
                        <dd class="col-sm-7">
                            @if ($asset->status === App\Enums\AssetStatus::Active)
                                <span class="sg-status-badge sg-status-badge-active">{{ $asset->status->label() }}</span>
                            @else
                                <span class="sg-status-badge sg-status-badge-inactive">{{ $asset->status->label() }}</span>
                            @endif
                        </dd>

                        <dt class="col-sm-5">Warranty expiry</dt>
                        <dd class="col-sm-7">{{ $asset->warranty_expires_at?->format('M j, Y') ?? '—' }}</dd>

                        @if ($asset->creator)
                            <dt class="col-sm-5">Created by</dt>
                            <dd class="col-sm-7">{{ $asset->creator->name }}</dd>
                        @endif

                        <dt class="col-sm-5">Recorded</dt>
                        <dd class="col-sm-7">{{ $asset->created_at?->format('M j, Y g:i A') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-semibold mb-3">Notes</h2>
                    @if ($asset->notes)
                        <p class="mb-0">{{ $asset->notes }}</p>
                    @else
                        <p class="text-muted mb-0">No notes recorded.</p>
                    @endif

                    @if ($asset->disposal)
                        <hr>
                        <h3 class="h6 fw-semibold mb-2">Disposal</h3>
                        <p class="small text-muted mb-0">
                            This asset was disposed on {{ $asset->disposal->disposed_at->format('M j, Y') }}
                            ({{ $asset->disposal->disposal_type->label() }}).
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
