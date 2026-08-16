@php
    $assetOptions = collect($assets)->mapWithKeys(
        fn ($asset): array => [$asset->id => "{$asset->name} ({$asset->asset_code})"]
    )->all();
@endphp

<div class="row">
    <div class="col-lg-8">
        <x-forms.select
            label="Asset"
            name="asset_id"
            :options="$assetOptions"
            :selected="old('asset_id', $selectedAssetId)"
            placeholder="Select asset"
            required
        />

        <x-forms.date-picker
            label="Disposal date"
            name="disposed_at"
            :value="old('disposed_at', now()->format('Y-m-d'))"
            required
        />

        <x-forms.select
            label="Disposal type"
            name="disposal_type"
            :options="App\Enums\AssetDisposalType::options()"
            :selected="old('disposal_type')"
            placeholder="Select disposal type"
            required
        />

        <x-forms.money-input
            label="Sale amount"
            name="sale_amount"
            :value="old('sale_amount')"
            help="Required when disposal type is Sold. Must be zero or greater."
        />

        <x-forms.input
            label="Buyer"
            name="buyer"
            placeholder="Buyer or recipient name"
            :value="old('buyer')"
        />

        <x-forms.textarea
            label="Reason"
            name="reason"
            rows="3"
            placeholder="Why is this asset being disposed?"
            :value="old('reason')"
        />

        <x-forms.textarea
            label="Notes"
            name="notes"
            rows="3"
            placeholder="Additional notes..."
            :value="old('notes')"
        />
    </div>
</div>
