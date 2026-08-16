@extends('layouts.admin', ['heading' => 'Member Profile'])

@section('title', $member->name)

@section('content')
    <x-ui.page-header :title="$member->name" :subtitle="$member->member_code">
        <x-slot:actions>
            <div class="d-flex flex-wrap gap-2">
                @can('update', $member)
                    <a href="{{ route('admin.members.edit', $member) }}" class="btn btn-primary">Edit</a>
                @endcan
                <a href="{{ route('admin.members.index') }}" class="btn btn-light">Back to Members</a>
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-4">
        @include('admin.members.partials.transaction-summary', ['summary' => $transactionSummary])
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="d-flex justify-content-center mb-3">
                        <x-admin.member-avatar :member="$member" size="lg" />
                    </div>
                    <h2 class="h5 mb-1">{{ $member->name }}</h2>
                    <p class="text-muted small mb-3">{{ $member->member_code }}</p>

                    @php
                        $badgeClass = match ($member->status) {
                            App\Enums\MemberStatus::Active => 'sg-status-badge-active',
                            App\Enums\MemberStatus::Pending => 'sg-status-badge-inactive',
                            App\Enums\MemberStatus::Suspended => 'sg-status-badge-warning',
                            default => 'sg-status-badge-inactive',
                        };
                    @endphp
                    <span class="sg-status-badge {{ $badgeClass }}">{{ $member->status->label() }}</span>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <h3 class="h6 fw-semibold mb-3">Membership</h3>
                    <dl class="row sg-profile-list mb-0">
                        <dt class="col-5">Current plan</dt>
                        <dd class="col-7">{{ $member->membershipPlan?->name ?? '—' }}</dd>

                        <dt class="col-5">Join date</dt>
                        <dd class="col-7">{{ $member->joined_at->format('M j, Y') }}</dd>

                        <dt class="col-5">Expiry date</dt>
                        <dd class="col-7">{{ $member->membership_expires_at?->format('M j, Y') ?? '—' }}</dd>

                        @if ($member->membershipPlan)
                            <dt class="col-5">Plan fee</dt>
                            <dd class="col-7">
                                {{ App\Support\MoneyFormatter::format($member->membershipPlan->membership_fee, $gymCurrency) }}
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h3 class="h6 fw-semibold mb-3">Personal Details</h3>
                    <dl class="row sg-profile-list mb-0">
                        <dt class="col-sm-4">Phone</dt>
                        <dd class="col-sm-8">{{ $member->phone }}</dd>

                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">{{ $member->email ?? '—' }}</dd>

                        <dt class="col-sm-4">RFID card</dt>
                        <dd class="col-sm-8">
                            @if ($member->activeRfidCard)
                                {{ $member->activeRfidCard->card_number }}
                            @else
                                —
                            @endif
                            @can('viewAny', App\Models\RfidCard::class)
                                <a href="{{ route('admin.rfid-cards.index', ['search' => $member->member_code]) }}" class="small ms-2">
                                    Manage cards
                                </a>
                            @endcan
                        </dd>

                        <dt class="col-sm-4">Gender</dt>
                        <dd class="col-sm-8">{{ $member->gender?->label() ?? '—' }}</dd>

                        <dt class="col-sm-4">Date of birth</dt>
                        <dd class="col-sm-8">{{ $member->date_of_birth?->format('M j, Y') ?? '—' }}</dd>

                        <dt class="col-sm-4">Address</dt>
                        <dd class="col-sm-8">{{ $member->address ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h3 class="h6 fw-semibold mb-3">Emergency Contact</h3>
                    <dl class="row sg-profile-list mb-0">
                        <dt class="col-sm-4">Name</dt>
                        <dd class="col-sm-8">{{ $member->emergency_contact_name ?? '—' }}</dd>

                        <dt class="col-sm-4">Phone</dt>
                        <dd class="col-sm-8">{{ $member->emergency_contact_phone ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            @if ($member->membershipPlan?->features)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h3 class="h6 fw-semibold mb-3">Plan Features</h3>
                        <ul class="mb-0 ps-3">
                            @foreach ($member->membershipPlan->features as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h3 class="h6 fw-semibold mb-0">Renewal History</h3>
                        @can('renew', $member)
                            <a href="{{ route('admin.members.renew.edit', $member) }}" class="btn btn-sm btn-light">Renew</a>
                        @endcan
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Plan</th>
                                    <th>Previous Expiry</th>
                                    <th>New Expiry</th>
                                    <th class="text-end pe-4">Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($member->membershipRenewals as $renewal)
                                    <tr>
                                        <td class="ps-4 text-muted">{{ $renewal->renewed_at->format('M j, Y') }}</td>
                                        <td>{{ $renewal->membershipPlan->name }}</td>
                                        <td class="text-muted">
                                            {{ $renewal->previous_expires_at?->format('M j, Y') ?? '—' }}
                                        </td>
                                        <td>{{ $renewal->new_expires_at->format('M j, Y') }}</td>
                                        <td class="text-end pe-4">
                                            @if ($renewal->invoice?->payment)
                                                <a href="{{ route('admin.invoices.show', $renewal->invoice) }}">
                                                    {{ $renewal->invoice->payment->receipt_number }}
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No renewals recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body p-0">
                    <div class="px-4 py-3 border-bottom">
                        <h3 class="h6 fw-semibold mb-0">POS Orders & Due Balances</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Order</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-end pe-4">Due</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($posOrders as $order)
                                    <tr>
                                        <td class="ps-4">
                                            <a href="{{ route('admin.orders.show', $order) }}">{{ $order->invoice_number }}</a>
                                        </td>
                                        <td class="text-muted">{{ $order->issued_at?->format('M j, Y') }}</td>
                                        <td>{{ $order->status->label() }}</td>
                                        <td class="text-end">{{ App\Support\MoneyFormatter::format($order->total, $gymCurrency) }}</td>
                                        <td class="text-end">{{ App\Support\MoneyFormatter::format($order->amountPaid(), $gymCurrency) }}</td>
                                        <td class="text-end pe-4 {{ $order->outstandingBalance() > 0 ? 'text-danger fw-semibold' : '' }}">
                                            {{ App\Support\MoneyFormatter::format($order->outstandingBalance(), $gymCurrency) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No POS orders yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body p-0">
                    <div class="px-4 py-3 border-bottom">
                        <h3 class="h6 fw-semibold mb-0">POS Payment History</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Receipt</th>
                                    <th>Order</th>
                                    <th class="text-end pe-4">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($posPayments as $payment)
                                    <tr>
                                        <td class="ps-4 text-muted">{{ $payment->paid_at->format('M j, Y') }}</td>
                                        <td>
                                            <a href="{{ route('admin.payments.show', $payment) }}">{{ $payment->receipt_number }}</a>
                                        </td>
                                        <td>
                                            @if ($payment->invoice)
                                                <a href="{{ route('admin.orders.show', $payment->invoice) }}">{{ $payment->invoice->invoice_number }}</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-end pe-4">{{ App\Support\MoneyFormatter::format($payment->amount, $gymCurrency) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No POS payments yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body p-0">
                    <div class="px-4 py-3 border-bottom">
                        <h3 class="h6 fw-semibold mb-0">Membership Payment History</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Type</th>
                                    <th>Receipt</th>
                                    <th class="text-end pe-4">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($membershipPayments as $payment)
                                    <tr>
                                        <td class="ps-4 text-muted">{{ $payment->paid_at->format('M j, Y') }}</td>
                                        <td>{{ $payment->type->label() }}</td>
                                        <td>
                                            <a href="{{ route('admin.payments.show', $payment) }}">{{ $payment->receipt_number }}</a>
                                        </td>
                                        <td class="text-end pe-4">{{ App\Support\MoneyFormatter::format($payment->amount, $gymCurrency) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No membership payments yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
