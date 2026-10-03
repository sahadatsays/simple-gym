@extends('layouts.admin', ['heading' => 'Reserve Locker'])

@section('title', 'Reserve Locker')

@section('content')
    @php
        $feeMap = $lockers->mapWithKeys(fn ($locker) => [(string) $locker->id => (float) $locker->monthly_fee])->all();
        $initialLocker = (string) old('locker_id', $selectedLockerId ?? '');
        $initialMonth = old('start_month', now()->format('Y-m'));
    @endphp

    <x-ui.page-header title="Reserve Locker" subtitle="Choose a member, locker, and start month">
        <x-slot:actions>
            <a href="{{ route('admin.locker-reservations.index') }}" class="btn btn-light">Back</a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <x-forms.error-summary />

            <form
                action="{{ route('admin.locker-reservations.store') }}"
                method="POST"
                x-data="{
                    fees: @js($feeMap),
                    lockerId: @js($initialLocker),
                    month: @js($initialMonth),
                    fee() {
                        return Number(this.fees[this.lockerId] ?? 0);
                    },
                    endDate() {
                        if (! this.month || ! this.month.includes('-')) {
                            return '';
                        }

                        const [year, month] = this.month.split('-').map(Number);
                        const last = new Date(year, month, 0);
                        const day = String(last.getDate()).padStart(2, '0');
                        const monthNumber = String(last.getMonth() + 1).padStart(2, '0');

                        return `${last.getFullYear()}-${monthNumber}-${day}`;
                    },
                }"
            >
                @csrf

                <x-forms.searchable-select
                    label="Member"
                    name="member_id"
                    :options="$members->mapWithKeys(fn ($member) => [$member->id => $member->name.' ('.$member->member_code.')'])->all()"
                    :selected="old('member_id', $selectedMemberId)"
                    placeholder="Search member..."
                    required
                />

                <div class="mb-3">
                    <label for="locker_id" class="form-label">Locker</label>
                    <select
                        name="locker_id"
                        id="locker_id"
                        class="form-select @error('locker_id') is-invalid @enderror"
                        x-model="lockerId"
                        required
                    >
                        <option value="">Select a locker</option>
                        @foreach ($lockers as $locker)
                            <option value="{{ $locker->id }}">
                                {{ $locker->locker_number }}@if ($locker->location) — {{ $locker->location }}@endif · {{ App\Support\MoneyFormatter::format($locker->monthly_fee, $gymCurrency) }}/month
                            </option>
                        @endforeach
                    </select>
                    @error('locker_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="start_month" class="form-label">Start month</label>
                    <input
                        type="month"
                        name="start_month"
                        id="start_month"
                        class="form-control @error('start_month') is-invalid @enderror"
                        x-model="month"
                        required
                    >
                    @error('start_month')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="calculated_end_date">End date</label>
                        <input id="calculated_end_date" class="form-control" type="text" readonly :value="endDate()">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="calculated_fee">Monthly fee</label>
                        <input id="calculated_fee" class="form-control" type="text" readonly :value="fee().toFixed(2)">
                    </div>
                </div>

                <div class="mb-3" x-show="fee() > 0" x-cloak>
                    <x-forms.select
                        label="Payment method"
                        name="payment_method"
                        :options="App\Enums\PaymentMethod::options()"
                        :selected="old('payment_method', 'cash')"
                    />
                </div>

                @error('reservation')
                    <div class="alert alert-danger">{{ $message }}</div>
                @enderror

                <button type="submit" class="btn btn-primary">Create reservation</button>
            </form>
        </div>
    </div>
@endsection
