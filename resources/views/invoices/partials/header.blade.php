<div class="invoice-header">
    <div class="invoice-brand">
        @if ($gym->logo_url)
            <img src="{{ $gym->logo_url }}" alt="{{ $gym->name }}" class="invoice-logo">
        @endif
        <div>
            <h1 class="invoice-company-name">{{ $gym->name }}</h1>
            @if ($gym->address)
                <div class="invoice-company-meta">{{ $gym->address }}</div>
            @endif
            @if ($gym->phone)
                <div class="invoice-company-meta">{{ $gym->phone }}</div>
            @endif
            @if ($gym->email)
                <div class="invoice-company-meta">{{ $gym->email }}</div>
            @endif
        </div>
    </div>

    <div class="invoice-meta">
        <div class="invoice-title">{{ $invoice->type->label() }} Invoice</div>
        <div><strong>{{ $invoice->invoice_number }}</strong></div>
        <div class="invoice-company-meta">Issued {{ $invoice->issued_at->format('M j, Y g:i A') }}</div>
        <div class="invoice-status invoice-status-{{ $invoice->status->value }}">{{ $invoice->status->label() }}</div>
    </div>
</div>
