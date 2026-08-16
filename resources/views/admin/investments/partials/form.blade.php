@php
    $categoryOptions = collect($categories)->mapWithKeys(
        fn ($category): array => [$category->id => $category->name]
    )->all();
@endphp

<div class="row">
    <div class="col-lg-8">
        @if ($investment)
            <div class="mb-3">
                <label class="form-label">Investment No.</label>
                <input type="text" class="form-control" value="{{ $investment->investment_number }}" disabled>
                <div class="form-text">Automatically generated when the investment is created.</div>
            </div>
        @endif

        <x-forms.date-picker
            label="Date"
            name="invested_at"
            :value="$investment?->invested_at?->format('Y-m-d')"
            required
        />

        <x-forms.select
            label="Category"
            name="investment_category_id"
            :options="$categoryOptions"
            :selected="old('investment_category_id', $investment?->investment_category_id)"
            placeholder="Select category"
            required
        />

        <x-forms.money-input
            label="Amount"
            name="amount"
            :value="$investment?->amount"
            required
        />

        <x-forms.select
            label="Payment method"
            name="payment_method"
            :options="App\Enums\PaymentMethod::options()"
            :selected="old('payment_method', $investment?->payment_method?->value ?? App\Enums\PaymentMethod::Cash->value)"
            required
        />

        <x-forms.textarea
            label="Description"
            name="description"
            rows="4"
            placeholder="Optional notes about this investment..."
            :value="$investment?->description"
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

        @if ($investment?->attachment_path)
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
                    <a href="{{ $investment->attachment_url }}" target="_blank" rel="noopener">View attachment</a>
                </div>
                @error('remove_attachment')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        @endif
    </div>
</div>
