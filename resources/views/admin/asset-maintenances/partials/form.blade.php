@php
    $assetOptions = collect($assets)->mapWithKeys(
        fn ($asset): array => [$asset->id => "{$asset->name} ({$asset->asset_code})"]
    )->all();

    $assetStatusOptions = ['' => 'Do not change status'] + App\Enums\AssetStatus::options();
@endphp

<div class="row">
    <div class="col-lg-8">
        @if ($maintenance)
            <div class="mb-3">
                <label class="form-label">Asset</label>
                <input
                    type="text"
                    class="form-control"
                    value="{{ $maintenance->asset?->name }} ({{ $maintenance->asset?->asset_code }})"
                    disabled
                >
            </div>
        @else
            <x-forms.select
                label="Asset"
                name="asset_id"
                :options="$assetOptions"
                :selected="old('asset_id', $selectedAssetId)"
                placeholder="Select asset"
                required
            />
        @endif

        <x-forms.date-picker
            label="Date"
            name="maintained_at"
            :value="$maintenance?->maintained_at?->format('Y-m-d') ?? now()->format('Y-m-d')"
            required
        />

        <x-forms.select
            label="Maintenance type"
            name="type"
            :options="App\Enums\AssetMaintenanceType::options()"
            :selected="old('type', $maintenance?->type?->value)"
            placeholder="Select type"
            required
        />

        <x-forms.money-input
            label="Cost"
            name="cost"
            :value="$maintenance?->cost"
            help="Optional. Must be zero or greater."
        />

        <x-forms.input
            label="Service provider"
            name="service_provider"
            placeholder="Vendor or technician name"
            :value="$maintenance?->service_provider"
        />

        <x-forms.textarea
            label="Description"
            name="description"
            rows="4"
            placeholder="Work performed, parts replaced, notes..."
            :value="$maintenance?->description"
        />

        <x-forms.date-picker
            label="Next maintenance date"
            name="next_maintenance_at"
            :value="$maintenance?->next_maintenance_at?->format('Y-m-d')"
        />

        <div class="mb-3">
            <label for="attachment" class="form-label">Attachment</label>
            <input
                type="file"
                name="attachment"
                id="attachment"
                class="form-control @error('attachment') is-invalid @enderror"
                accept=".pdf,.jpg,.jpeg,.png,.webp"
            >
            <div class="form-text">Optional. PDF or image up to 5 MB.</div>
            @error('attachment')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        @if ($maintenance?->attachment_path)
            <div class="mb-3">
                <div class="form-check">
                    <input
                        class="form-check-input @error('remove_attachment') is-invalid @enderror"
                        type="checkbox"
                        name="remove_attachment"
                        id="remove_attachment"
                        value="1"
                        @checked(old('remove_attachment'))
                    >
                    <label class="form-check-label" for="remove_attachment">
                        Remove current attachment
                    </label>
                </div>
                <div class="form-text">
                    Current file:
                    <a href="{{ $maintenance->attachment_url }}" target="_blank" rel="noopener">View attachment</a>
                </div>
            </div>
        @endif

        <x-forms.select
            label="Update asset status"
            name="asset_status"
            :options="$assetStatusOptions"
            :selected="old('asset_status', '')"
            help="Optional. Leave unchanged unless you want to update the asset status."
        />
    </div>
</div>
