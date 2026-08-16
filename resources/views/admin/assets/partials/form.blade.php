@php
    $categoryOptions = collect($categories)->mapWithKeys(
        fn ($category): array => [$category->id => $category->name]
    )->all();
    $isEditing = $asset !== null;
@endphp

<div class="row">
    <div class="col-lg-8">
        @if ($asset)
            <div class="mb-3">
                <label class="form-label">Asset code</label>
                <input type="text" class="form-control" value="{{ $asset->asset_code }}" disabled>
                <div class="form-text">Automatically generated when the asset is created.</div>
            </div>
        @endif

        <x-forms.input
            label="Asset name"
            name="name"
            placeholder="Commercial Treadmill"
            :value="$asset?->name"
            required
        />

        <x-forms.select
            label="Category"
            name="asset_category_id"
            :options="$categoryOptions"
            :selected="old('asset_category_id', $asset?->asset_category_id)"
            placeholder="Select category"
            required
        />

        <x-forms.date-picker
            label="Purchase date"
            name="purchased_at"
            :value="$asset?->purchased_at?->format('Y-m-d') ?? now()->format('Y-m-d')"
            required
        />

        <div class="row">
            <div class="col-md-6">
                <x-forms.money-input
                    label="Purchase price"
                    name="purchase_price"
                    :value="$asset?->purchase_price"
                    required
                />
            </div>
            <div class="col-md-6">
                <x-forms.money-input
                    label="Current value"
                    name="current_value"
                    :value="$asset?->current_value"
                    help="Optional on create. Defaults to purchase price and cannot exceed it."
                />
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <x-forms.input
                    label="Supplier"
                    name="supplier"
                    placeholder="Vendor name"
                    :value="$asset?->supplier"
                />
            </div>
            <div class="col-md-6">
                <x-forms.input
                    label="Location"
                    name="location"
                    placeholder="Cardio Zone"
                    :value="$asset?->location"
                />
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <x-forms.select
                    label="Condition"
                    name="condition"
                    :options="App\Enums\AssetCondition::options()"
                    :selected="old('condition', $asset?->condition?->value ?? App\Enums\AssetCondition::New->value)"
                    :required="$isEditing"
                />
            </div>
            <div class="col-md-6">
                <x-forms.select
                    label="Status"
                    name="status"
                    :options="App\Enums\AssetStatus::options()"
                    :selected="old('status', $asset?->status?->value ?? App\Enums\AssetStatus::Active->value)"
                    :required="$isEditing"
                />
            </div>
        </div>

        <x-forms.date-picker
            label="Warranty expiry"
            name="warranty_expires_at"
            :value="$asset?->warranty_expires_at?->format('Y-m-d')"
        />

        <x-forms.textarea
            label="Notes"
            name="notes"
            rows="4"
            placeholder="Optional notes about this asset..."
            :value="$asset?->notes"
        />
    </div>
</div>
