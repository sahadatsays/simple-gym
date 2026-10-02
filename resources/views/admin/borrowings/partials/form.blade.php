<div class="row">
    <div class="col-lg-8">
        @if ($borrowing)
            <div class="mb-3">
                <label class="form-label">Borrowing No.</label>
                <input type="text" class="form-control" value="{{ $borrowing->borrowing_no }}" disabled>
                <div class="form-text">Automatically generated when the borrowing is created.</div>
            </div>
        @endif

        <x-forms.input
            label="Lender name"
            name="lender_name"
            :value="$borrowing?->lender_name"
            required
        />

        <x-forms.input
            label="Lender phone"
            name="lender_phone"
            :value="$borrowing?->lender_phone"
        />

        <x-forms.date-picker
            label="Borrowing date"
            name="borrowing_date"
            :value="$borrowing?->borrowing_date?->format('Y-m-d') ?? now()->format('Y-m-d')"
            required
        />

        <x-forms.money-input
            label="Amount"
            name="amount"
            :value="$borrowing?->amount"
            required
        />

        <x-forms.input
            label="Purpose"
            name="purpose"
            :value="$borrowing?->purpose"
        />

        <x-forms.date-picker
            label="Due date"
            name="due_date"
            :value="$borrowing?->due_date?->format('Y-m-d')"
            help="Cannot be before the borrowing date."
        />

        <x-forms.select
            label="Payment method"
            name="payment_method"
            :options="App\Enums\PaymentMethod::options()"
            :selected="old('payment_method', $borrowing?->payment_method?->value ?? App\Enums\PaymentMethod::Cash->value)"
            required
        />

        @if ($borrowing)
            <x-forms.select
                label="Status"
                name="status"
                :options="App\Enums\BorrowingStatus::options()"
                :selected="old('status', $borrowing->status->value)"
                required
            />
        @else
            <div class="mb-3">
                <label class="form-label">Status</label>
                <input type="text" class="form-control" value="Active" disabled>
                <div class="form-text">New borrowings start as active.</div>
            </div>
        @endif

        <x-forms.textarea
            label="Description"
            name="description"
            rows="4"
            placeholder="Optional notes about this borrowing..."
            :value="$borrowing?->description"
        />
    </div>
</div>
