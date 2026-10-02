@extends('layouts.admin', ['heading' => 'Borrowings'])

@section('title', 'Borrowings')

@section('content')
    <x-ui.page-header title="Borrowings" subtitle="Money borrowed by the gym that must be returned">
        <x-slot:actions>
            @can('create', App\Models\Borrowing::class)
                <a href="{{ route('admin.borrowings.create') }}" class="btn btn-primary">
                    Add Borrowing
                </a>
            @endcan
            @can('borrowings.edit')
                <a href="{{ route('admin.borrowings.repayments.create') }}" class="btn btn-light">
                    Record Repayment
                </a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-admin.filter-bar class="mb-4">
        <form action="{{ route('admin.borrowings.index') }}" method="GET" class="sg-filter-grid">
            <x-admin.filter-field label="Search" for="search">
                <input
                    type="search"
                    name="search"
                    id="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Borrowing no., lender, phone, or purpose..."
                    class="form-control ps-2"
                >
            </x-admin.filter-field>

            <x-admin.filter-field label="Status" for="status">
                <select name="status" id="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach (App\Enums\BorrowingStatus::options() as $value => $label)
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
                    <a href="{{ route('admin.borrowings.index') }}" class="btn btn-light">Reset</a>
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
                            <th class="ps-4">Borrowing No.</th>
                            <th>Date</th>
                            <th>Lender</th>
                            <th>Due</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($borrowings as $borrowing)
                            <tr>
                                <td class="ps-4">
                                    <a href="{{ route('admin.borrowings.show', $borrowing) }}" class="fw-semibold text-decoration-none">
                                        {{ $borrowing->borrowing_no }}
                                    </a>
                                    @if ($borrowing->purpose)
                                        <div class="small text-muted text-truncate" style="max-width: 240px;">
                                            {{ $borrowing->purpose }}
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $borrowing->borrowing_date->format('M j, Y') }}</td>
                                <td>
                                    {{ $borrowing->lender_name }}
                                    @if ($borrowing->lender_phone)
                                        <div class="small text-muted">{{ $borrowing->lender_phone }}</div>
                                    @endif
                                </td>
                                <td>{{ $borrowing->due_date?->format('M j, Y') ?? '—' }}</td>
                                <td>{{ $borrowing->payment_method->label() }}</td>
                                <td>
                                    <span class="sg-status-badge {{ $borrowing->status->badgeClass() }}">
                                        {{ $borrowing->status->label() }}
                                    </span>
                                </td>
                                <td class="text-end">{{ App\Support\MoneyFormatter::format($borrowing->amount, $gymCurrency) }}</td>
                                <td class="text-end pe-4">
                                    <x-admin.borrowing-actions :borrowing="$borrowing" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="sg-empty-state">
                                        <h3 class="h6 mb-1">No borrowings found</h3>
                                        <p class="text-muted small mb-0">Try adjusting your search or filters.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($borrowings->hasPages())
            <div class="card-footer bg-white border-top-0 px-4 py-3">
                {{ $borrowings->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
