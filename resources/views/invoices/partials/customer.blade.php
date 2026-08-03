<div class="invoice-parties">
    <div class="invoice-party">
        <h2 class="invoice-section-title">Bill To</h2>
        @if ($invoice->member)
            <div class="fw-semibold">{{ $invoice->member->name }}</div>
            <div class="invoice-muted">{{ $invoice->member->member_code }}</div>
            @if ($invoice->member->phone)
                <div class="invoice-muted">{{ $invoice->member->phone }}</div>
            @endif
            @if ($invoice->member->email)
                <div class="invoice-muted">{{ $invoice->member->email }}</div>
            @endif
        @else
            <div class="fw-semibold">Walk-in Customer</div>
        @endif
    </div>

    @if ($invoice->membershipPlan)
        <div class="invoice-party">
            <h2 class="invoice-section-title">Membership</h2>
            <div class="fw-semibold">{{ $invoice->membershipPlan->name }}</div>
            @if ($invoice->member?->membership_expires_at)
                <div class="invoice-muted">
                    Expires {{ $invoice->member->membership_expires_at->format('M j, Y') }}
                </div>
            @endif
            @if ($invoice->isRenewal() && $invoice->membershipRenewal?->previous_expires_at)
                <div class="invoice-muted">
                    Previous expiry {{ $invoice->membershipRenewal->previous_expires_at->format('M j, Y') }}
                </div>
            @endif
        </div>
    @endif
</div>
