@extends('layouts.admin', ['heading' => 'Locker Reservation'])

@section('title', 'Locker Reservation')

@section('content')
    <x-ui.page-header title="Locker Reservation" subtitle="{{ $reservation->locker?->locker_number }} · {{ $reservation->member?->name }}">
        <x-slot:actions>
            <a href="{{ route('admin.locker-reservations.index') }}" class="btn btn-light">Back</a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-forms.error-summary />

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <dl class="row sg-profile-list mb-0">
                        <dt class="col-sm-4">Locker</dt>
                        <dd class="col-sm-8">
                            @if ($reservation->locker)
                                <a href="{{ route('admin.lockers.show', $reservation->locker) }}">{{ $reservation->locker->locker_number }}</a>
                            @else
                                —
                            @endif
                        </dd>

                        <dt class="col-sm-4">Member</dt>
                        <dd class="col-sm-8">{{ $reservation->member?->name ?? '—' }}</dd>

                        <dt class="col-sm-4">Start date</dt>
                        <dd class="col-sm-8">{{ $reservation->start_date->format('M j, Y') }}</dd>

                        <dt class="col-sm-4">End date</dt>
                        <dd class="col-sm-8">{{ $reservation->end_date->format('M j, Y') }}</dd>

                        <dt class="col-sm-4">Monthly fee</dt>
                        <dd class="col-sm-8">{{ App\Support\MoneyFormatter::format($reservation->monthly_fee, $gymCurrency) }}</dd>

                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">
                            <span class="sg-status-badge {{ $reservation->status->badgeClass() }}">{{ $reservation->status->label() }}</span>
                        </dd>

                        <dt class="col-sm-4">Invoice</dt>
                        <dd class="col-sm-8">
                            @if ($reservation->invoice)
                                <a href="{{ route('admin.invoices.show', $reservation->invoice) }}">{{ $reservation->invoice->invoice_number }}</a>
                            @else
                                —
                            @endif
                        </dd>

                        <dt class="col-sm-4">Created by</dt>
                        <dd class="col-sm-8">{{ $reservation->creator?->name ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        @if ($reservation->status !== App\Enums\LockerReservationStatus::Cancelled)
            <div class="col-lg-4">
                @can('renew', $reservation)
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <h2 class="h6">Renew</h2>
                            <p class="text-muted small">Renewal adds the next month. This reservation stays unchanged.</p>
                            <dl class="row small mb-3">
                                <dt class="col-5">Next period</dt>
                                <dd class="col-7">{{ \Illuminate\Support\Carbon::parse($nextPeriod['start_date'])->format('M j, Y') }} – {{ \Illuminate\Support\Carbon::parse($nextPeriod['end_date'])->format('M j, Y') }}</dd>
                                <dt class="col-5">Monthly fee</dt>
                                <dd class="col-7">{{ App\Support\MoneyFormatter::format($reservation->locker->monthly_fee ?? 0, $gymCurrency) }}</dd>
                            </dl>
                            <form action="{{ route('admin.locker-reservations.renew', $reservation) }}" method="POST">
                                @csrf
                                @if ((float) ($reservation->locker->monthly_fee ?? 0) > 0)
                                    <x-forms.select
                                        label="Payment method"
                                        name="payment_method"
                                        :options="App\Enums\PaymentMethod::options()"
                                        :selected="old('payment_method', 'cash')"
                                    />
                                @endif
                                <button type="submit" class="btn btn-primary">Renew reservation</button>
                            </form>
                        </div>
                    </div>
                @endcan

                @if ($reservation->isActive())
                @can('cancel', $reservation)
                    <form
                        action="{{ route('admin.locker-reservations.cancel', $reservation) }}"
                        method="POST"
                        onsubmit="return confirm('Cancel this reservation? The history and invoice stay on record.');"
                    >
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-outline-danger">Cancel reservation</button>
                    </form>
                @endcan
                @endif
            </div>
        @endif
    </div>
@endsection
