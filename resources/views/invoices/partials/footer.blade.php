<div class="invoice-footer">
    <div class="invoice-qr">
        {!! $qr_code_svg !!}
        <div class="invoice-muted">Scan to verify invoice</div>
    </div>

    <div class="invoice-footer-text">
        @if ($gym->receipt_footer)
            <p>{{ $gym->receipt_footer }}</p>
        @endif
        <p class="invoice-muted">{{ $verification_url }}</p>
    </div>
</div>
