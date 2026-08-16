@extends('layouts.admin', ['heading' => 'Expense Details'])

@section('title', $expense->expense_number)

@section('content')
    <x-ui.page-header :title="$expense->expense_number" subtitle="Expense details">
        <x-slot:actions>
            @can('update', $expense)
                <a href="{{ route('admin.expenses.edit', $expense) }}" class="btn btn-primary">Edit</a>
            @endcan
            <a href="{{ route('admin.expenses.index') }}" class="btn btn-light">Back to Expenses</a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-semibold mb-3">Expense</h2>
                    <dl class="row sg-profile-list mb-0">
                        <dt class="col-sm-5">Expense No.</dt>
                        <dd class="col-sm-7">{{ $expense->expense_number }}</dd>

                        <dt class="col-sm-5">Date</dt>
                        <dd class="col-sm-7">{{ $expense->expensed_at->format('M j, Y') }}</dd>

                        <dt class="col-sm-5">Category</dt>
                        <dd class="col-sm-7">{{ $expense->category?->name ?? '—' }}</dd>

                        <dt class="col-sm-5">Amount</dt>
                        <dd class="col-sm-7">{{ App\Support\MoneyFormatter::format($expense->amount, $gymCurrency) }}</dd>

                        <dt class="col-sm-5">Payment method</dt>
                        <dd class="col-sm-7">{{ $expense->payment_method->label() }}</dd>

                        <dt class="col-sm-5">Paid to</dt>
                        <dd class="col-sm-7">{{ $expense->paid_to ?: '—' }}</dd>

                        <dt class="col-sm-5">Status</dt>
                        <dd class="col-sm-7">
                            @if ($expense->status === App\Enums\ExpenseStatus::Paid)
                                <span class="sg-status-badge sg-status-badge-active">{{ $expense->status->label() }}</span>
                            @else
                                <span class="sg-status-badge sg-status-badge-inactive">{{ $expense->status->label() }}</span>
                            @endif
                        </dd>

                        @if ($expense->description)
                            <dt class="col-sm-5">Description</dt>
                            <dd class="col-sm-7">{{ $expense->description }}</dd>
                        @endif

                        @if ($expense->creator)
                            <dt class="col-sm-5">Created by</dt>
                            <dd class="col-sm-7">{{ $expense->creator->name }}</dd>
                        @endif

                        <dt class="col-sm-5">Recorded</dt>
                        <dd class="col-sm-7">{{ $expense->created_at?->format('M j, Y g:i A') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-semibold mb-3">Attachment</h2>

                    @if ($expense->attachment_path)
                        <p class="text-muted small mb-3">Supporting document uploaded with this expense.</p>
                        <a href="{{ $expense->attachment_url }}" class="btn btn-light" target="_blank" rel="noopener">
                            View Attachment
                        </a>
                    @else
                        <p class="text-muted mb-0">No attachment uploaded.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
