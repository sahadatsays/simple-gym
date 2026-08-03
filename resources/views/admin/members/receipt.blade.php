@extends('layouts.admin', ['heading' => 'Receipt'])

@section('title', 'Receipt '.$payment->receipt_number)

@section('content')
    <x-ui.page-header
        :title="$invoice->isRenewal() ? 'Renewal Receipt' : 'Registration Receipt'"
        :subtitle="$payment->receipt_number"
    >
        <x-slot:actions>
            <button type="button" class="btn btn-light d-print-none" onclick="window.print()">Print Receipt</button>
            <a href="{{ route('admin.members.show', $member) }}" class="btn btn-primary d-print-none">View Member</a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="card border-0 shadow-sm sg-receipt mx-auto" style="max-width: 720px;">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h1 class="h4 fw-bold mb-1">{{ config('app.name') }}</h1>
                    <p class="text-muted small mb-0">
                        {{ $invoice->isRenewal() ? 'Membership Renewal Receipt' : 'Member Registration Receipt' }}
                    </p>
                </div>
                <div class="text-end">
                    <div class="fw-semibold">{{ $payment->receipt_number }}</div>
                    <div class="text-muted small">{{ $payment->paid_at->format('M j, Y g:i A') }}</div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <h2 class="h6 text-muted text-uppercase small mb-2">Member</h2>
                    <div class="fw-semibold">{{ $member->name }}</div>
                    <div class="text-muted small">{{ $member->member_code }}</div>
                    <div class="text-muted small">{{ $member->phone }}</div>
                </div>
                <div class="col-md-6">
                    <h2 class="h6 text-muted text-uppercase small mb-2">Membership</h2>
                    <div class="fw-semibold">{{ $invoice->membershipPlan->name }}</div>
                    <div class="text-muted small">
                        Joined {{ $member->joined_at->format('M j, Y') }}
                    </div>
                    <div class="text-muted small">
                        Expires {{ $member->membership_expires_at?->format('M j, Y') ?? '—' }}
                    </div>
                    @if ($invoice->isRenewal() && $invoice->membershipRenewal)
                        <div class="text-muted small">
                            Previous expiry {{ $invoice->membershipRenewal->previous_expires_at?->format('M j, Y') ?? '—' }}
                        </div>
                    @endif
                </div>
            </div>

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

                <dt class="col-sm-4">Payment method</dt>
                <dd class="col-sm-8">{{ $payment->payment_method->label() }}</dd>

                @if ($payment->reference)
                    <dt class="col-sm-4">Reference</dt>
                    <dd class="col-sm-8">{{ $payment->reference }}</dd>
                @endif

                <dt class="col-sm-4">Status</dt>
                <dd class="col-sm-8">
                    <span class="sg-status-badge sg-status-badge-active">Paid</span>
                </dd>

                @if ($member->activeRfidCard)
                    <dt class="col-sm-4">RFID card</dt>
                    <dd class="col-sm-8">{{ $member->activeRfidCard->card_number }}</dd>
                @endif
            </dl>

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
