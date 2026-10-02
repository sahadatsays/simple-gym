@extends('layouts.admin', ['heading' => 'Repayments'])

@section('title', 'Repayments')

@section('content')
    <x-ui.page-header title="Repayments" subtitle="Money returned against gym borrowings">
        <x-slot:actions>
            @can('borrowings.edit')
                <a href="{{ route('admin.borrowings.repayments.create') }}" class="btn btn-primary">
                    Record Repayment
                </a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    @include('admin.borrowings.partials.section-nav')

    <x-admin.filter-bar class="mb-4">
        <form action="{{ route('admin.borrowings.repayments.index') }}" method="GET" class="sg-filter-grid">
            <x-admin.filter-field label="Search" for="search">
                <input
                    type="search"
                    name="search"
                    id="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Repayment no., borrowing no., or lender..."
                    class="form-control ps-2"
                >
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
                    <a href="{{ route('admin.borrowings.repayments.index') }}" class="btn btn-light">Reset</a>
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
                            <th class="ps-4">Repayment No.</th>
                            <th>Date</th>
                            <th>Borrowing</th>
                            <th>Lender</th>
                            <th>Method</th>
                            <th>Created By</th>
                            <th class="text-end pe-4">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($repayments as $repayment)
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $repayment->repayment_no }}</td>
                                <td>{{ $repayment->repayment_date->format('M j, Y') }}</td>
                                <td>
                                    <a href="{{ route('admin.borrowings.show', $repayment->borrowing) }}" class="fw-semibold text-decoration-none">
                                        {{ $repayment->borrowing->borrowing_no }}
                                    </a>
                                </td>
                                <td>{{ $repayment->borrowing->lender_name }}</td>
                                <td>{{ $repayment->payment_method->label() }}</td>
                                <td>{{ $repayment->creator?->name ?? '—' }}</td>
                                <td class="text-end pe-4">{{ App\Support\MoneyFormatter::format($repayment->amount, $gymCurrency) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="sg-empty-state">
                                        <h3 class="h6 mb-1">No repayments found</h3>
                                        <p class="text-muted small mb-0">Try adjusting your search or filters.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($repayments->hasPages())
            <div class="card-footer bg-white border-top-0 px-4 py-3">
                {{ $repayments->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
