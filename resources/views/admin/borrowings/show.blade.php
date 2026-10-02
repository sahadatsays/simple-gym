@extends('layouts.admin', ['heading' => 'Borrowing Details'])

@section('title', $borrowing->borrowing_no)

@section('content')
    <x-ui.page-header :title="$borrowing->borrowing_no" subtitle="Borrowing details">
        <x-slot:actions>
            @can('update', $borrowing)
                @if ($borrowing->acceptsRepayment())
                    <a href="{{ route('admin.borrowings.repayments.create', ['borrowing_id' => $borrowing->id]) }}" class="btn btn-primary">
                        Record Repayment
                    </a>
                @endif
                <a href="{{ route('admin.borrowings.edit', $borrowing) }}" @class(['btn', 'btn-primary' => ! $borrowing->acceptsRepayment(), 'btn-light' => $borrowing->acceptsRepayment()])>
                    Edit
                </a>
            @endcan
            <a href="{{ route('admin.borrowings.index') }}" class="btn btn-light">Back to Borrowings</a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="row g-3 g-lg-4 mb-4">
        <div class="col-12">
            <x-admin.detail-section title="Borrowing" class="h-100">
                <x-admin.detail-list>
                    <x-admin.detail-item label="Borrowing No" :value="$borrowing->borrowing_no" />
                    <x-admin.detail-item label="Lender">
                        {{ $borrowing->lender_name }}
                        @if ($borrowing->lender_phone)
                            <div class="small text-muted">{{ $borrowing->lender_phone }}</div>
                        @endif
                    </x-admin.detail-item>
                    <x-admin.detail-item label="Borrowing Date" :value="$borrowing->borrowing_date->format('M j, Y')" />
                    <x-admin.detail-item label="Original Amount" :value="App\Support\MoneyFormatter::format($borrowing->amount, $gymCurrency)" />
                    <x-admin.detail-item label="Total Repaid" :value="App\Support\MoneyFormatter::format($borrowing->total_repaid, $gymCurrency)" />
                    <x-admin.detail-item label="Remaining Amount" :value="App\Support\MoneyFormatter::format($borrowing->remaining_amount, $gymCurrency)" />
                    <x-admin.detail-item label="Due Date" :value="$borrowing->due_date?->format('M j, Y') ?? '—'" />
                    <x-admin.detail-item label="Purpose" :value="$borrowing->purpose ?: '—'" />
                    <x-admin.detail-item label="Status">
                        <span class="sg-status-badge {{ $borrowing->status->badgeClass() }}">
                            {{ $borrowing->status->label() }}
                        </span>
                    </x-admin.detail-item>
                </x-admin.detail-list>
            </x-admin.detail-section>
        </div>
    </div>

    <x-admin.detail-table-section title="Repayment History" class="mb-4">
        <table class="table table-hover align-middle mb-0 sg-data-table">
            <thead>
                <tr>
                    <th class="ps-4">Repayment No</th>
                    <th>Date</th>
                    <th class="text-end">Amount</th>
                    <th>Payment Method</th>
                    <th class="pe-4">Created By</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($borrowing->repayments as $repayment)
                    <tr>
                        <td class="ps-4 text-nowrap">{{ $repayment->repayment_no }}</td>
                        <td class="text-nowrap">{{ $repayment->repayment_date->format('M j, Y') }}</td>
                        <td class="text-end text-nowrap">{{ App\Support\MoneyFormatter::format($repayment->amount, $gymCurrency) }}</td>
                        <td>{{ $repayment->payment_method->label() }}</td>
                        <td class="pe-4">{{ $repayment->creator?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="sg-empty-state">
                                <h3 class="h6 mb-1">No repayments recorded</h3>
                                <p class="text-muted small mb-0">Returns against this borrowing will appear here.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-admin.detail-table-section>

    @if ($borrowing->description || $borrowing->creator)
        <x-admin.detail-section title="Additional Information">
            <x-admin.detail-list>
                <x-admin.detail-item label="Payment Method" :value="$borrowing->payment_method->label()" />
                @if ($borrowing->creator)
                    <x-admin.detail-item label="Created By" :value="$borrowing->creator->name" />
                @endif
                @if ($borrowing->created_at)
                    <x-admin.detail-item label="Recorded" :value="$borrowing->created_at->format('M j, Y g:i A')" />
                @endif
            </x-admin.detail-list>

            @if ($borrowing->description)
                <div class="mt-3 pt-3 border-top">
                    <h3 class="h6 fw-semibold mb-2">Description</h3>
                    <p class="mb-0">{{ $borrowing->description }}</p>
                </div>
            @endif
        </x-admin.detail-section>
    @endif
@endsection
