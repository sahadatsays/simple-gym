@extends('layouts.admin', ['heading' => 'Payment Details'])

@section('title', 'Payment '.$payment->receipt_number)

@section('content')
    <x-ui.page-header :title="$payment->receipt_number" subtitle="Payment and invoice details">
        <x-slot:actions>
            @if ($invoice)
                <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-light">View Invoice</a>
                <a href="{{ route('admin.invoices.print', $invoice) }}" class="btn btn-light" target="_blank">Print A4</a>
                <a href="{{ route('admin.invoices.thermal', $invoice) }}" class="btn btn-light" target="_blank">Thermal Receipt</a>
            @else
                <a href="{{ route('admin.payments.receipt', $payment) }}" class="btn btn-light" target="_blank">Thermal Receipt</a>
            @endif
            @can('create', App\Models\Payment::class)
                <a href="{{ route('admin.payments.create') }}" class="btn btn-primary">Receive Payment</a>
            @endcan
            <a href="{{ route('admin.payments.index') }}" class="btn btn-light">Back to History</a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-semibold mb-3">Payment</h2>
                    <dl class="row sg-profile-list mb-0">
                        <dt class="col-sm-5">Receipt</dt>
                        <dd class="col-sm-7">{{ $payment->receipt_number }}</dd>

                        <dt class="col-sm-5">Type</dt>
                        <dd class="col-sm-7">{{ $payment->type->label() }}</dd>

                        <dt class="col-sm-5">Method</dt>
                        <dd class="col-sm-7">{{ $payment->payment_method->label() }}</dd>

                        <dt class="col-sm-5">Paid at</dt>
                        <dd class="col-sm-7">{{ $payment->paid_at->format('M j, Y g:i A') }}</dd>

                        <dt class="col-sm-5">Amount paid</dt>
                        <dd class="col-sm-7">{{ App\Support\MoneyFormatter::format($payment->amount, $gymCurrency) }}</dd>

                        @if ((float) $payment->discount_amount > 0)
                            <dt class="col-sm-5">Discount</dt>
                            <dd class="col-sm-7">{{ App\Support\MoneyFormatter::format($payment->discount_amount, $gymCurrency) }}</dd>
                        @endif

                        @if ($payment->reference)
                            <dt class="col-sm-5">Reference</dt>
                            <dd class="col-sm-7">{{ $payment->reference }}</dd>
                        @endif

                        @if ($payment->notes)
                            <dt class="col-sm-5">Notes</dt>
                            <dd class="col-sm-7">{{ $payment->notes }}</dd>
                        @endif

                        <dt class="col-sm-5">Status</dt>
                        <dd class="col-sm-7">
                            <span class="sg-status-badge sg-status-badge-active">{{ $payment->status->label() }}</span>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 fw-semibold mb-3">Member</h2>
                    @if ($payment->member)
                        <dl class="row sg-profile-list mb-0">
                            <dt class="col-sm-4">Name</dt>
                            <dd class="col-sm-8">
                                <a href="{{ route('admin.members.show', $payment->member) }}">{{ $payment->member->name }}</a>
                            </dd>
                            <dt class="col-sm-4">Member ID</dt>
                            <dd class="col-sm-8">{{ $payment->member->member_code }}</dd>
                            <dt class="col-sm-4">Phone</dt>
                            <dd class="col-sm-8">{{ $payment->member->phone }}</dd>
                        </dl>
                    @else
                        <p class="text-muted mb-0">Walk-in customer</p>
                    @endif
                </div>
            </div>

            @if ($invoice)
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-3">Invoice</h2>
                        <dl class="row sg-profile-list mb-4">
                            <dt class="col-sm-4">Invoice number</dt>
                            <dd class="col-sm-8">{{ $invoice->invoice_number }}</dd>

                            <dt class="col-sm-4">Category</dt>
                            <dd class="col-sm-8">{{ $invoice->type->label() }}</dd>

                            <dt class="col-sm-4">Issued</dt>
                            <dd class="col-sm-8">{{ $invoice->issued_at->format('M j, Y g:i A') }}</dd>

                            @if ($invoice->membershipPlan)
                                <dt class="col-sm-4">Plan</dt>
                                <dd class="col-sm-8">{{ $invoice->membershipPlan->name }}</dd>
                            @endif

                            <dt class="col-sm-4">Status</dt>
                            <dd class="col-sm-8">
                                <span class="sg-status-badge sg-status-badge-active">{{ $invoice->status->label() }}</span>
                            </dd>

                            <dt class="col-sm-4">Outstanding</dt>
                            <dd class="col-sm-8">
                                {{ App\Support\MoneyFormatter::format($invoice->outstandingBalance(), $gymCurrency) }}
                            </dd>
                        </dl>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invoice->line_items as $item)
                                        <tr>
                                            <td>{{ $item['description'] }}</td>
                                            <td class="text-end">
                                                {{ App\Support\MoneyFormatter::format($item['amount'], $gymCurrency) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Subtotal</th>
                                        <th class="text-end">
                                            {{ App\Support\MoneyFormatter::format($invoice->subtotal, $gymCurrency) }}
                                        </th>
                                    </tr>
                                    @if ((float) $invoice->discount_amount > 0)
                                        <tr>
                                            <th>Discount</th>
                                            <th class="text-end text-danger">
                                                -{{ App\Support\MoneyFormatter::format($invoice->discount_amount, $gymCurrency) }}
                                            </th>
                                        </tr>
                                    @endif
                                    <tr>
                                        <th>Total</th>
                                        <th class="text-end">
                                            {{ App\Support\MoneyFormatter::format($invoice->total, $gymCurrency) }}
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
