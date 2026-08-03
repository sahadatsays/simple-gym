<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invoice->invoice_number }} | Thermal</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            color: #000;
            margin: 0;
            background: #f3f4f6;
        }
        .thermal-paper {
            width: 80mm;
            margin: 1rem auto;
            background: #fff;
            padding: 10px 8px;
            font-size: 11px;
            line-height: 1.35;
        }
        .thermal-center { text-align: center; }
        .thermal-logo { max-height: 48px; max-width: 120px; margin-bottom: 6px; }
        .thermal-title { font-size: 14px; font-weight: 700; margin: 0 0 4px; }
        .thermal-muted { color: #444; font-size: 10px; }
        .thermal-divider {
            border-top: 1px dashed #999;
            margin: 8px 0;
        }
        .thermal-row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 3px;
        }
        .thermal-row strong { font-weight: 700; }
        .thermal-item { margin-bottom: 6px; }
        .thermal-qr svg { width: 88px; height: 88px; }
        .thermal-toolbar {
            width: 80mm;
            margin: 0 auto 1rem;
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }
        .thermal-toolbar button,
        .thermal-toolbar a {
            font-size: 11px;
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 0.35rem;
            padding: 0.35rem 0.6rem;
            text-decoration: none;
            color: #000;
        }
        @media print {
            body { background: #fff; }
            .thermal-paper { margin: 0; width: 80mm; }
            .thermal-toolbar, .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="thermal-toolbar no-print">
        <button type="button" onclick="window.print()">Print</button>
        <a href="{{ route('admin.invoices.print', $invoice) }}" target="_blank">A4</a>
    </div>

    <div class="thermal-paper">
        <div class="thermal-center">
            @if ($gym->logo_url)
                <img src="{{ $gym->logo_url }}" alt="{{ $gym->name }}" class="thermal-logo">
            @endif
            <div class="thermal-title">{{ $gym->name }}</div>
            @if ($gym->address)
                <div class="thermal-muted">{{ $gym->address }}</div>
            @endif
            @if ($gym->phone)
                <div class="thermal-muted">{{ $gym->phone }}</div>
            @endif
        </div>

        <div class="thermal-divider"></div>

        <div class="thermal-center">
            <div class="thermal-title">{{ $invoice->type->label() }}</div>
            <div>{{ $invoice->invoice_number }}</div>
            <div class="thermal-muted">{{ $invoice->issued_at->format('M j, Y g:i A') }}</div>
            <div class="thermal-muted">{{ $invoice->status->label() }}</div>
        </div>

        <div class="thermal-divider"></div>

        @if ($invoice->member)
            <div class="thermal-item">
                <strong>{{ $invoice->member->name }}</strong>
                <div class="thermal-muted">{{ $invoice->member->member_code }}</div>
            </div>
        @else
            <div class="thermal-item"><strong>Walk-in Customer</strong></div>
        @endif

        @if ($invoice->membershipPlan)
            <div class="thermal-item thermal-muted">{{ $invoice->membershipPlan->name }}</div>
        @endif

        <div class="thermal-divider"></div>

        @foreach ($invoice->line_items as $item)
            <div class="thermal-item">
                <div>{{ $item['description'] }}</div>
                @if (isset($item['quantity']))
                    <div class="thermal-row thermal-muted">
                        <span>{{ $item['quantity'] }} x {{ App\Support\MoneyFormatter::format($item['unit_price'] ?? 0, $currency) }}</span>
                    </div>
                @endif
                <div class="thermal-row">
                    <span></span>
                    <strong>{{ App\Support\MoneyFormatter::format($item['amount'], $currency) }}</strong>
                </div>
            </div>
        @endforeach

        <div class="thermal-divider"></div>

        <div class="thermal-row"><span>Subtotal</span><span>{{ App\Support\MoneyFormatter::format($summary['subtotal'], $currency) }}</span></div>
        @if ($summary['discount'] > 0)
            <div class="thermal-row"><span>Discount</span><span>-{{ App\Support\MoneyFormatter::format($summary['discount'], $currency) }}</span></div>
        @endif
        <div class="thermal-row"><strong>Total</strong><strong>{{ App\Support\MoneyFormatter::format($summary['total'], $currency) }}</strong></div>
        <div class="thermal-row"><span>Paid</span><span>{{ App\Support\MoneyFormatter::format($summary['amount_paid'], $currency) }}</span></div>
        <div class="thermal-row"><strong>Balance</strong><strong>{{ App\Support\MoneyFormatter::format($summary['outstanding_balance'], $currency) }}</strong></div>

        @if (! empty($payment_summary))
            <div class="thermal-divider"></div>
            <div class="thermal-center"><strong>Payment</strong></div>
            @foreach ($payment_summary as $entry)
                <div class="thermal-muted thermal-center">{{ $entry['receipt_number'] }}</div>
                <div class="thermal-row thermal-muted">
                    <span>{{ $entry['method'] }}</span>
                    <span>{{ App\Support\MoneyFormatter::format($entry['amount'], $currency) }}</span>
                </div>
            @endforeach
        @endif

        <div class="thermal-divider"></div>

        <div class="thermal-center thermal-qr">
            {!! $qr_code_svg !!}
        </div>

        @if ($gym->receipt_footer)
            <div class="thermal-center thermal-muted" style="margin-top:8px;">{{ $gym->receipt_footer }}</div>
        @endif
    </div>

    <script>
        window.addEventListener('load', () => {
            if (new URLSearchParams(window.location.search).get('autoprint') === '1') {
                window.print();
            }
        });
    </script>
</body>
</html>
