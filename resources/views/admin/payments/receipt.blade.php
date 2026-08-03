@extends('layouts.admin', ['heading' => 'Receipt'])

@section('title', 'Receipt '.$payment->receipt_number)

@section('content')
    <x-ui.page-header
        title="Payment Receipt"
        :subtitle="$payment->receipt_number"
    >
        <x-slot:actions>
            <button type="button" class="btn btn-light d-print-none" onclick="window.print()">Print Receipt</button>
            @if (request()->boolean('pos'))
                <a href="{{ route('admin.pos.index') }}" class="btn btn-outline-primary d-print-none">New Sale</a>
            @endif
            <a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-primary d-print-none">Payment Details</a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="card border-0 shadow-sm sg-receipt mx-auto" style="max-width: 720px;">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h1 class="h4 fw-bold mb-1">{{ config('app.name') }}</h1>
                    <p class="text-muted small mb-0">{{ $payment->type->label() }} Receipt</p>
                </div>
                <div class="text-end">
                    <div class="fw-semibold">{{ $payment->receipt_number }}</div>
                    <div class="text-muted small">{{ $payment->paid_at->format('M j, Y g:i A') }}</div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <h2 class="h6 text-muted text-uppercase small mb-2">Customer</h2>
                    @if ($member)
                        <div class="fw-semibold">{{ $member->name }}</div>
                        <div class="text-muted small">{{ $member->member_code }}</div>
                        <div class="text-muted small">{{ $member->phone }}</div>
                    @else
                        <div class="fw-semibold">Walk-in Customer</div>
                    @endif
                </div>
                <div class="col-md-6">
                    <h2 class="h6 text-muted text-uppercase small mb-2">Payment</h2>
                    <div class="fw-semibold">{{ $payment->payment_method->label() }}</div>
                    @if ($payment->reference)
                        <div class="text-muted small">Ref: {{ $payment->reference }}</div>
                    @endif
                    @if ($invoice?->membershipPlan)
                        <div class="text-muted small mt-2">Plan: {{ $invoice->membershipPlan->name }}</div>
                    @endif
                </div>
            </div>

            @if ($invoice)
                <div class="table-responsive mb-4">
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
                                <th>Total Paid</th>
                                <th class="text-end">
                                    {{ App\Support\MoneyFormatter::format($payment->amount, $gymCurrency) }}
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <dl class="row sg-profile-list mb-4">
                    <dt class="col-sm-4">Invoice</dt>
                    <dd class="col-sm-8">{{ $invoice->invoice_number }}</dd>
                </dl>
            @endif

            <p class="text-muted small mb-0 text-center">
                Thank you for your payment to {{ config('app.name') }}.
            </p>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        @media print {
            .sg-sidebar,
            .sg-topbar,
            .d-print-none {
                display: none !important;
            }

            .sg-main {
                margin-left: 0 !important;
            }

            .sg-receipt {
                box-shadow: none !important;
                border: 1px solid #e2e8f0 !important;
            }
        }
    </style>
@endpush

@if (request()->boolean('pos'))
    @push('scripts')
        <script>
            window.addEventListener('load', () => {
                window.print();
            });
        </script>
    @endpush
@endif
