@extends('layouts.admin', ['heading' => 'Invoices'])

@section('title', 'Invoices')

@section('content')
    <x-ui.page-header title="Invoices" subtitle="Browse registration, renewal, and POS invoices">
        <x-slot:actions>
            @can('create', App\Models\Payment::class)
                <a href="{{ route('admin.payments.create') }}" class="btn btn-primary">
                    <i class="bi bi-wallet2 me-1" aria-hidden="true"></i>
                    Receive Payment
                </a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-admin.filter-bar class="mb-4">
        <form action="{{ route('admin.invoices.index') }}" method="GET" class="sg-filter-grid">
            <x-admin.filter-field label="Search" for="search">
                <input
                    type="search"
                    name="search"
                    id="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Invoice number, member..."
                    class="form-control ps-2"
                >
            </x-admin.filter-field>

            <x-admin.filter-field label="Type" for="type">
                <select name="type" id="type" class="form-select">
                    <option value="">All types</option>
                    @foreach (App\Enums\InvoiceType::options() as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Status" for="status">
                <select name="status" id="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach (App\Enums\InvoiceStatus::options() as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>
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
                    <a href="{{ route('admin.invoices.index') }}" class="btn btn-light">Reset</a>
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
                            <th class="ps-4">Invoice</th>
                            <th>Member</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Issued</th>
                            <th class="text-end">Total</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoices as $invoice)
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $invoice->invoice_number }}</td>
                                <td>
                                    @if ($invoice->member)
                                        <a href="{{ route('admin.members.show', $invoice->member) }}" class="text-decoration-none">
                                            {{ $invoice->member->full_name }}
                                        </a>
                                    @else
                                        <span class="text-muted">Walk-in</span>
                                    @endif
                                </td>
                                <td>{{ $invoice->type->label() }}</td>
                                <td>
                                    <span @class([
                                        'badge',
                                        'text-bg-success' => $invoice->status === App\Enums\InvoiceStatus::Paid,
                                        'text-bg-warning' => $invoice->status === App\Enums\InvoiceStatus::Unpaid,
                                        'text-bg-secondary' => $invoice->status === App\Enums\InvoiceStatus::Void,
                                    ])>
                                        {{ $invoice->status->label() }}
                                    </span>
                                </td>
                                <td>{{ $invoice->issued_at?->format('M j, Y') ?? '—' }}</td>
                                <td class="text-end">{{ $currencySymbol }}{{ number_format((float) $invoice->total, 2) }}</td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-sm btn-light">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    No invoices found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($invoices->hasPages())
            <div class="card-footer bg-white border-0">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
@endsection
