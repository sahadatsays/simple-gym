@extends('layouts.admin', ['heading' => $locker->locker_number])

@section('title', $locker->locker_number)

@section('content')
    <x-ui.page-header :title="$locker->locker_number" subtitle="Locker details">
        <x-slot:actions>
            @can('update', $locker)
                <a href="{{ route('admin.lockers.edit', $locker) }}" class="btn btn-primary">Edit</a>
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
    </div>
@endsection
