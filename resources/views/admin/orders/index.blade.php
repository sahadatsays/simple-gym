@extends('layouts.admin', ['heading' => 'Orders'])

@section('title', 'Orders')

@section('content')
    <x-ui.page-header title="Manage Orders" subtitle="Track POS sales, due balances, and payment history">
        <x-slot:actions>
            @can('create', App\Models\Payment::class)
                <a href="{{ route('admin.pos.index') }}" class="btn btn-primary">
                    <i class="bi bi-shop-window me-1" aria-hidden="true"></i>
                    New POS Sale
                </a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-admin.filter-bar class="mb-4">
        <form action="{{ route('admin.orders.index') }}" method="GET" class="sg-filter-grid">
            <x-admin.filter-field label="Search" for="search">
                <input
                    type="search"
                    name="search"
                    id="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Order number, member..."
                    class="form-control ps-2"
                >
            </x-admin.filter-field>

            <x-admin.filter-field label="Status" for="status">
                <select name="status" id="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="From" for="from_date">
                <input
                    type="text"
                    name="from_date"
                    id="from_date"
                    value="{{ $filters['from_date'] ?? '' }}"
                    placeholder="Select date"
                    data-picker="date"
                    class="form-control sg-date-picker"
                    autocomplete="off"
                >
            </x-admin.filter-field>

            <x-admin.filter-field label="To" for="to_date">
                <input
                    type="text"
                    name="to_date"
                    id="to_date"
                    value="{{ $filters['to_date'] ?? '' }}"
                    placeholder="Select date"
                    data-picker="date"
                    class="form-control sg-date-picker"
                    autocomplete="off"
                >
            </x-admin.filter-field>

            <x-admin.filter-field label="Actions" class="sg-filter-actions-field">
                <div class="sg-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-light">Reset</a>
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
                            <th class="ps-4">Order</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Issued</th>
                            <th>Due date</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end pe-4">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr onclick="window.location='{{ route('admin.orders.show', $order) }}'" role="link" style="cursor: pointer;">
                                <td class="ps-4 fw-semibold">{{ $order->invoice_number }}</td>
                                <td>
                                    @if ($order->member)
                                        <div>{{ $order->member->name }}</div>
                                        <div class="small text-muted">{{ $order->member->member_code }}</div>
                                    @else
                                        <span class="text-muted">Walk-in</span>
                                    @endif
                                </td>
                                <td>
                                    <x-ui.badge :variant="match ($order->status) {
                                        App\Enums\InvoiceStatus::Paid => 'success',
                                        App\Enums\InvoiceStatus::Partial => 'warning',
                                        App\Enums\InvoiceStatus::Unpaid => 'danger',
                                        default => 'secondary',
                                    }">
                                        {{ $order->status->label() }}
                                    </x-ui.badge>
                                </td>
                                <td class="text-muted">{{ $order->issued_at?->format('M j, Y') }}</td>
                                <td class="text-muted">{{ $order->due_at?->format('M j, Y') ?? '—' }}</td>
                                <td class="text-end">{{ App\Support\MoneyFormatter::format($order->total, $gymCurrency) }}</td>
                                <td class="text-end">{{ App\Support\MoneyFormatter::format($order->amountPaid(), $gymCurrency) }}</td>
                                <td class="text-end pe-4 fw-semibold {{ $order->outstandingBalance() > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ App\Support\MoneyFormatter::format($order->outstandingBalance(), $gymCurrency) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">No orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($orders->hasPages())
            <div class="card-footer bg-white border-0">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
@endsection
