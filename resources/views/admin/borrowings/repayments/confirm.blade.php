@php
    $paymentMethod = App\Enums\PaymentMethod::tryFrom($data['payment_method'] ?? '');
@endphp

@extends('layouts.admin', ['heading' => 'Confirm Repayment'])

@section('title', 'Confirm Repayment')

@section('content')
    <x-ui.page-header title="Confirm Repayment" subtitle="Review this return before it is saved">
        <x-slot:actions>
            <a
                href="{{ route('admin.borrowings.repayments.create', array_filter([
                    'borrowing_id' => $data['borrowing_id'],
                    'repayment_date' => $data['repayment_date'],
                    'amount' => $data['amount'],
                    'payment_method' => $data['payment_method'],
                    'description' => $data['description'] ?? null,
                ])) }}"
                class="btn btn-light"
            >
                Edit Details
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="alert alert-warning border-0 shadow-sm mb-4">
        <strong>This repayment is added to the history.</strong>
        Previous repayments stay as they are and this return cannot be edited afterward.
    </div>

    <x-admin.detail-section title="Repayment Summary" class="mb-4">
        <x-admin.detail-list>
            <x-admin.detail-item label="Repayment No." value="Assigned when you confirm" />
            <x-admin.detail-item label="Borrowing" :value="$borrowing->borrowing_no . ' — ' . $borrowing->lender_name" />
            <x-admin.detail-item label="Borrowed amount" :value="App\Support\MoneyFormatter::format($borrowing->amount, $gymCurrency)" />
            <x-admin.detail-item label="Total repaid" :value="App\Support\MoneyFormatter::format($borrowing->total_repaid, $gymCurrency)" />
            <x-admin.detail-item label="Remaining amount" :value="App\Support\MoneyFormatter::format($borrowing->remaining_amount, $gymCurrency)" />
            <x-admin.detail-item label="Repayment date" :value="\Illuminate\Support\Carbon::parse($data['repayment_date'])->format('M j, Y')" />
            <x-admin.detail-item label="Amount" :value="App\Support\MoneyFormatter::format($amount, $gymCurrency)" />
            <x-admin.detail-item label="Payment method" :value="$paymentMethod?->label() ?? '—'" />
            <x-admin.detail-item label="Description" :value="$data['description'] ?? '—'" />
            <x-admin.detail-item label="Remaining after this repayment" :value="App\Support\MoneyFormatter::format($remainingAfter, $gymCurrency)" />
            <x-admin.detail-item label="Status after save" :value="$fullyRepaid ? 'Fully Repaid' : 'Partially Repaid'" />
        </x-admin.detail-list>
    </x-admin.detail-section>

    <x-ui.card>
        <form action="{{ route('admin.borrowings.repayments.store') }}" method="POST">
            @csrf

            @foreach ($data as $key => $value)
                @if (filled($value))
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach

            <div class="d-flex flex-wrap gap-2">
                <x-ui.button type="submit">Confirm Repayment</x-ui.button>
                <a
                    href="{{ route('admin.borrowings.repayments.create', ['borrowing_id' => $borrowing->id]) }}"
                    class="btn btn-light"
                >
                    Back
                </a>
            </div>
        </form>
    </x-ui.card>
@endsection
