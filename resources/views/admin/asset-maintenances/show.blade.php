@extends('layouts.admin', ['heading' => 'Maintenance Details'])

@section('title', 'Maintenance Record')

@section('content')
    <x-ui.page-header :title="$maintenance->type->label()" :subtitle="$maintenance->asset?->name">
        <x-slot:actions>
            <div class="d-flex flex-wrap gap-2">
                @if ($maintenance->asset)
                    <a href="{{ route('admin.assets.show', $maintenance->asset) }}" class="btn btn-light">View Asset</a>
                @endif
                @can('update', $maintenance)
                    <a href="{{ route('admin.asset-maintenances.edit', $maintenance) }}" class="btn btn-primary">Edit</a>
                @endcan
                <a href="{{ route('admin.asset-maintenances.index') }}" class="btn btn-light">Back to Maintenance</a>
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="row g-4">
        <div class="col-lg-6">
            <x-admin.detail-section title="Maintenance Details">
                <x-admin.detail-list>
                    <x-admin.detail-item label="Asset">
                        @if ($maintenance->asset)
                            <a href="{{ route('admin.assets.show', $maintenance->asset) }}">
                                {{ $maintenance->asset->name }}
                            </a>
                            <div class="small text-muted">{{ $maintenance->asset->asset_code }}</div>
                        @else
                            —
                        @endif
                    </x-admin.detail-item>
                    <x-admin.detail-item label="Date" :value="$maintenance->maintained_at->format('M j, Y')" />
                    <x-admin.detail-item label="Type" :value="$maintenance->type->label()" />
                    <x-admin.detail-item label="Cost">
                        @if ($maintenance->cost !== null)
                            {{ App\Support\MoneyFormatter::format($maintenance->cost, $gymCurrency) }}
                        @else
                            —
                        @endif
                    </x-admin.detail-item>
                    <x-admin.detail-item label="Service provider" :value="$maintenance->service_provider ?: '—'" />
                    <x-admin.detail-item
                        label="Next maintenance"
                        :value="$maintenance->next_maintenance_at?->format('M j, Y') ?? '—'"
                    />
                    @if ($maintenance->creator)
                        <x-admin.detail-item label="Recorded by" :value="$maintenance->creator->name" />
                    @endif
                </x-admin.detail-list>
            </x-admin.detail-section>
        </div>

        <div class="col-lg-6">
            <x-admin.detail-section title="Description & Attachment">
                @if ($maintenance->description)
                    <p class="mb-3">{{ $maintenance->description }}</p>
                @else
                    <p class="text-muted mb-3">No description provided.</p>
                @endif

                @if ($maintenance->attachment_path)
                    <a href="{{ $maintenance->attachment_url }}" class="btn btn-light" target="_blank" rel="noopener">
                        View Attachment
                    </a>
                @else
                    <p class="text-muted mb-0">No attachment uploaded.</p>
                @endif
            </x-admin.detail-section>
        </div>
    </div>
@endsection
