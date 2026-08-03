@extends('layouts.admin', ['heading' => 'Payments'])

@section('title', 'Payments')

@section('content')
    <x-ui.page-header title="Payment History" subtitle="View and filter all received payments">
        <x-slot:actions>
            @can('create', App\Models\Payment::class)
                <a href="{{ route('admin.payments.create') }}" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-1" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
                    </svg>
                    Receive Payment
                </a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-admin.filter-bar class="mb-4">
        <form action="{{ route('admin.payments.index') }}" method="GET" class="sg-filter-grid">
            <x-admin.filter-field label="Search" for="search">
                <input
                    type="search"
                    name="search"
                    id="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Receipt, invoice, member..."
                    class="form-control ps-2"
                >
            </x-admin.filter-field>

            <x-admin.filter-field label="Type" for="type">
                <select name="type" id="type" class="form-select">
                    <option value="">All types</option>
                    @foreach (App\Enums\PaymentType::options() as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Method" for="payment_method">
                <select name="payment_method" id="payment_method" class="form-select">
                    <option value="">All methods</option>
                    @foreach (App\Enums\PaymentMethod::options() as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['payment_method'] ?? '') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="From" for="from_date">
                <input type="date" name="from_date" id="from_date" value="{{ $filters['from_date'] ?? '' }}" class="form-control">
            </x-admin.filter-field>

            <x-admin.filter-field label="To" for="to_date">
                <input type="date" name="to_date" id="to_date" value="{{ $filters['to_date'] ?? '' }}" class="form-control">
            </x-admin.filter-field>

            <x-admin.filter-field label="Actions" class="sg-filter-actions-field">
                <div class="sg-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.payments.index') }}" class="btn btn-light">Reset</a>
                </div>
            </x-admin.filter-field>
        </form>
    </x-admin.filter-bar>

    <div class="card border-0 shadow-sm sg-data-table-card">
        <div class="card-body p-0">
            <div class="table-responsive sg-data-table-wrapper">
                <table class="table table-hover align-middle mb-0 sg-data-table">
                    <thead>
                        <tr>
                            <th class="ps-4">Receipt</th>
                            <th>Member</th>
                            <th>Type</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Paid at</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold">{{ $payment->receipt_number }}</div>
                                    @if ($payment->invoice)
                                        <div class="text-muted small">{{ $payment->invoice->invoice_number }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($payment->member)
                                        <div class="fw-semibold">{{ $payment->member->name }}</div>
                                        <div class="text-muted small">{{ $payment->member->member_code }}</div>
                                    @else
                                        <span class="text-muted">Walk-in</span>
                                    @endif
                                </td>
                                <td>{{ $payment->type->label() }}</td>
                                <td>{{ $payment->payment_method->label() }}</td>
                                <td>{{ App\Support\MoneyFormatter::format($payment->amount, $gymCurrency) }}</td>
                                <td class="text-muted">{{ $payment->paid_at->format('M j, Y g:i A') }}</td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-sm btn-light">Details</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="sg-empty-state">
                                        <h3 class="h6 mb-1">No payments found</h3>
                                        <p class="text-muted small mb-0">Try adjusting your search or filters.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($payments->hasPages())
            <div class="card-footer bg-white border-top-0 px-4 py-3">
                {{ $payments->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
