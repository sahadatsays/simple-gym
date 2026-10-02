@php
    $borrowingOptions = $borrowings->map(fn ($borrowing): array => [
        'id' => $borrowing->id,
        'borrowed' => App\Support\MoneyFormatter::format($borrowing->amount, $gymCurrency),
        'repaid' => App\Support\MoneyFormatter::format($borrowing->total_repaid, $gymCurrency),
        'remaining' => App\Support\MoneyFormatter::format($borrowing->remaining_amount, $gymCurrency),
        'remaining_amount' => $borrowing->remaining_amount,
    ])->values();

    $selectedId = old('borrowing_id', request('borrowing_id', $selectedBorrowingId));
@endphp

@extends('layouts.admin', ['heading' => 'Record Repayment'])

@section('title', 'Record Repayment')

@section('content')
    <x-ui.page-header title="Record Repayment" subtitle="Return money against an open borrowing" />

    @if ($borrowings->isEmpty())
        <x-ui.card>
            <p class="text-muted mb-0">No open borrowings can receive a repayment.</p>
        </x-ui.card>
    @else
        <x-ui.card>
            <form
                action="{{ route('admin.borrowings.repayments.confirm') }}"
                method="POST"
                x-data="{
                    borrowings: @js($borrowingOptions),
                    borrowingId: @js((string) $selectedId),
                    get selected() {
                        return this.borrowings.find((borrowing) => String(borrowing.id) === String(this.borrowingId)) ?? null;
                    },
                }"
            >
                @csrf

                <div class="row">
                    <div class="col-lg-8">
                        <div class="mb-3">
                            <label for="borrowing_id" class="form-label">
                                Borrowing <span class="text-danger">*</span>
                            </label>
                            <select
                                name="borrowing_id"
                                id="borrowing_id"
                                x-model="borrowingId"
                                @class(['form-select', 'is-invalid' => $errors->has('borrowing_id')])
                                required
                            >
                                <option value="">Select a borrowing</option>
                                @foreach ($borrowings as $borrowing)
                                    <option value="{{ $borrowing->id }}" @selected((string) $selectedId === (string) $borrowing->id)>
                                        {{ $borrowing->borrowing_no }} — {{ $borrowing->lender_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('borrowing_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="border rounded p-3 bg-light mb-4" x-cloak x-bind:class="{ 'd-none': !selected }">
                            <h2 class="h6 fw-semibold mb-3">Balance</h2>
                            <dl class="row sg-profile-list mb-0">
                                <dt class="col-sm-5">Borrowed amount</dt>
                                <dd class="col-sm-7" x-text="selected?.borrowed"></dd>
                                <dt class="col-sm-5">Total repaid</dt>
                                <dd class="col-sm-7" x-text="selected?.repaid"></dd>
                                <dt class="col-sm-5">Remaining amount</dt>
                                <dd class="col-sm-7 fw-semibold" x-text="selected?.remaining"></dd>
                            </dl>
                        </div>

                        <x-forms.date-picker
                            label="Repayment date"
                            name="repayment_date"
                            :value="request('repayment_date', now()->format('Y-m-d'))"
                            required
                        />

                        <x-forms.money-input
                            label="Amount"
                            name="amount"
                            :value="request('amount')"
                            help="Cannot be more than the remaining amount."
                            required
                        />

                        <x-forms.select
                            label="Payment method"
                            name="payment_method"
                            :options="App\Enums\PaymentMethod::options()"
                            :selected="old('payment_method', request('payment_method', App\Enums\PaymentMethod::Cash->value))"
                            required
                        />

                        <x-forms.textarea
                            label="Description"
                            name="description"
                            rows="3"
                            placeholder="Optional note about this return..."
                            :value="request('description')"
                        />
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <x-ui.button type="submit">Review & Confirm</x-ui.button>
                    <a href="{{ route('admin.borrowings.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </x-ui.card>
    @endif
@endsection
