@props(['member'])

@php
    $badgeClass = match ($member->status) {
        App\Enums\MemberStatus::Active => 'sg-status-badge-active',
        App\Enums\MemberStatus::Pending => 'sg-status-badge-inactive',
        App\Enums\MemberStatus::Suspended => 'sg-status-badge-warning',
        default => 'sg-status-badge-inactive',
    };
@endphp

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
            <h2 class="h6 fw-semibold mb-0">{{ __('members.show.membership') }}</h2>
            <span class="sg-status-badge {{ $badgeClass }}">{{ $member->status->label() }}</span>
        </div>

        <dl class="row sg-profile-list mb-0">
            <dt class="col-5">{{ __('members.show.current_plan') }}</dt>
            <dd class="col-7">{{ $member->membershipPlan?->name ?? '—' }}</dd>

            <dt class="col-5">{{ __('members.show.join_date') }}</dt>
            <dd class="col-7">{{ $member->joined_at->format('M j, Y') }}</dd>

            <dt class="col-5">{{ __('members.show.expiry_date') }}</dt>
            <dd class="col-7">{{ $member->membership_expires_at?->format('M j, Y') ?? '—' }}</dd>

            @if ($member->membershipPlan)
                <dt class="col-5">{{ __('members.show.plan_fee') }}</dt>
                <dd class="col-7">
                    {{ App\Support\MoneyFormatter::format($member->membershipPlan->membership_fee, $gymCurrency) }}
                </dd>
            @endif

            @if ($member->activeRfidCard)
                <dt class="col-5">{{ __('members.show.rfid_card') }}</dt>
                <dd class="col-7">{{ $member->activeRfidCard->card_number }}</dd>
            @endif
        </dl>

        <div class="alert alert-light border small mb-0 mt-3">
            {{ __('members.form.membership_readonly_help') }}
        </div>

        @can('renew', $member)
            @if ($member->isRenewable())
                <a href="{{ route('admin.members.renew.edit', $member) }}" class="btn btn-sm btn-outline-primary w-100 mt-3">
                    <i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>
                    {{ __('members.renew_membership') }}
                </a>
            @endif
        @endcan
    </div>
</div>
