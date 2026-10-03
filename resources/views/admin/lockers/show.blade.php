@extends('layouts.admin', ['heading' => $locker->locker_number])

@section('title', $locker->locker_number)

@section('content')
    <x-ui.page-header :title="$locker->locker_number" subtitle="Locker details">
        <x-slot:actions>
            @can('create', App\Models\LockerReservation::class)
                @if ($locker->canBeReserved())
                    <a href="{{ route('admin.locker-reservations.create', ['locker_id' => $locker->id]) }}" class="btn btn-primary">Reserve</a>
                @endif
            @endcan
            @can('update', $locker)
                <a href="{{ route('admin.lockers.edit', $locker) }}" class="btn btn-light">Edit</a>
            @endcan
            <a href="{{ route('admin.lockers.index') }}" class="btn btn-light">Back to Lockers</a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <dl class="row sg-profile-list mb-0">
                        <dt class="col-sm-4">Locker number</dt>
                        <dd class="col-sm-8">{{ $locker->locker_number }}</dd>

                        <dt class="col-sm-4">Location</dt>
                        <dd class="col-sm-8">{{ $locker->location ?: '—' }}</dd>

                        <dt class="col-sm-4">Type / Category</dt>
                        <dd class="col-sm-8">{{ $locker->category ?: '—' }}</dd>

                        <dt class="col-sm-4">Monthly fee</dt>
                        <dd class="col-sm-8">{{ App\Support\MoneyFormatter::format($locker->monthly_fee, $gymCurrency) }}</dd>

                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">
                            <span class="sg-status-badge {{ $locker->status->badgeClass() }}">{{ $locker->status->label() }}</span>
                        </dd>

                        <dt class="col-sm-4">Notes</dt>
                        <dd class="col-sm-8">{{ $locker->notes ?: '—' }}</dd>

                        <dt class="col-sm-4">Created by</dt>
                        <dd class="col-sm-8">{{ $locker->creator?->name ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6">Reservations</h2>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Start</th>
                                    <th class="d-none d-md-table-cell">End</th>
                                    <th>Fee</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($locker->reservations as $reservation)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.locker-reservations.show', $reservation) }}">{{ $reservation->member?->name ?? '—' }}</a>
                                        </td>
                                        <td>{{ $reservation->start_date->format('M j, Y') }}</td>
                                        <td class="d-none d-md-table-cell">{{ $reservation->end_date->format('M j, Y') }}</td>
                                        <td>{{ App\Support\MoneyFormatter::format($reservation->monthly_fee, $gymCurrency) }}</td>
                                        <td>
                                            <span class="sg-status-badge {{ $reservation->status->badgeClass() }}">{{ $reservation->status->label() }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted">No reservations yet.</td>
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
