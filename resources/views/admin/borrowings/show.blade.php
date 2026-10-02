@extends('layouts.admin', ['heading' => 'Borrowing Details'])

@section('title', $borrowing->borrowing_no)

@section('content')
    <x-ui.page-header :title="$borrowing->borrowing_no" subtitle="Borrowing details">
        <x-slot:actions>
            @can('update', $borrowing)
                <a href="{{ route('admin.borrowings.edit', $borrowing) }}" class="btn btn-primary">Edit</a>
            @endcan
            <a href="{{ route('admin.borrowings.index') }}" class="btn btn-light">Back to Borrowings</a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-semibold mb-3">Borrowing</h2>
                    <dl class="row sg-profile-list mb-0">
                        <dt class="col-sm-5">Borrowing No.</dt>
                        <dd class="col-sm-7">{{ $borrowing->borrowing_no }}</dd>

                        <dt class="col-sm-5">Lender</dt>
                        <dd class="col-sm-7">{{ $borrowing->lender_name }}</dd>

                        <dt class="col-sm-5">Phone</dt>
                        <dd class="col-sm-7">{{ $borrowing->lender_phone ?: '—' }}</dd>

                        <dt class="col-sm-5">Borrowing date</dt>
                        <dd class="col-sm-7">{{ $borrowing->borrowing_date->format('M j, Y') }}</dd>

                        <dt class="col-sm-5">Due date</dt>
                        <dd class="col-sm-7">{{ $borrowing->due_date?->format('M j, Y') ?? '—' }}</dd>

                        <dt class="col-sm-5">Amount</dt>
                        <dd class="col-sm-7">{{ App\Support\MoneyFormatter::format($borrowing->amount, $gymCurrency) }}</dd>

                        <dt class="col-sm-5">Remaining</dt>
                        <dd class="col-sm-7">{{ App\Support\MoneyFormatter::format($borrowing->remaining_amount, $gymCurrency) }}</dd>

                        <dt class="col-sm-5">Payment method</dt>
                        <dd class="col-sm-7">{{ $borrowing->payment_method->label() }}</dd>

                        <dt class="col-sm-5">Purpose</dt>
                        <dd class="col-sm-7">{{ $borrowing->purpose ?: '—' }}</dd>

                        <dt class="col-sm-5">Status</dt>
                        <dd class="col-sm-7">
                            <span class="sg-status-badge {{ $borrowing->status->badgeClass() }}">
                                {{ $borrowing->status->label() }}
                            </span>
                        </dd>

                        @if ($borrowing->description)
                            <dt class="col-sm-5">Description</dt>
                            <dd class="col-sm-7">{{ $borrowing->description }}</dd>
                        @endif

                        @if ($borrowing->creator)
                            <dt class="col-sm-5">Created by</dt>
                            <dd class="col-sm-7">{{ $borrowing->creator->name }}</dd>
                        @endif

                        <dt class="col-sm-5">Recorded</dt>
                        <dd class="col-sm-7">{{ $borrowing->created_at?->format('M j, Y g:i A') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-semibold mb-3">Repayment history</h2>

                    @if ($borrowing->repayments->isEmpty())
                        <p class="text-muted mb-0">No repayments recorded. This borrowing can still be deleted.</p>
                    @else
                        <p class="text-muted small">These returns are kept. A borrowing with history cannot be deleted.</p>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($borrowing->repayments as $repayment)
                                        <tr>
                                            <td>{{ $repayment->repayment_no }}</td>
                                            <td>{{ $repayment->repayment_date->format('M j, Y') }}</td>
                                            <td>{{ $repayment->payment_method->label() }}</td>
                                            <td class="text-end">{{ App\Support\MoneyFormatter::format($repayment->amount, $gymCurrency) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
