<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invoice->invoice_number }} | {{ $gym->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #0f172a;
            margin: 0;
            background: #f8fafc;
        }
        .invoice-shell {
            max-width: 820px;
            margin: 2rem auto;
            background: #fff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }
        .invoice-body { padding: 2.5rem; }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            gap: 2rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #e2e8f0;
        }
        .invoice-brand { display: flex; gap: 1rem; align-items: flex-start; }
        .invoice-logo { max-height: 72px; max-width: 140px; object-fit: contain; }
        .invoice-company-name { font-size: 1.5rem; margin: 0 0 0.35rem; }
        .invoice-company-meta, .invoice-muted { color: #64748b; font-size: 0.875rem; }
        .invoice-meta { text-align: right; }
        .invoice-title { font-size: 1.125rem; font-weight: 700; margin-bottom: 0.35rem; }
        .invoice-status {
            display: inline-block;
            margin-top: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .invoice-status-paid { background: #dcfce7; color: #166534; }
        .invoice-status-unpaid { background: #fef3c7; color: #92400e; }
        .invoice-status-void { background: #fee2e2; color: #991b1b; }
        .invoice-parties {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .invoice-section-title {
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
            margin: 0 0 0.5rem;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }
        .invoice-table th,
        .invoice-table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 0.5rem;
            text-align: left;
        }
        .invoice-table th { background: #f8fafc; font-size: 0.8125rem; }
        .invoice-table-compact th,
        .invoice-table-compact td { padding: 0.5rem; font-size: 0.8125rem; }
        .text-end { text-align: right; }
        .invoice-summary-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .invoice-totals {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1rem;
        }
        .invoice-total-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.4rem 0;
        }
        .invoice-total-row-strong {
            font-weight: 700;
            font-size: 1.05rem;
            border-top: 1px solid #e2e8f0;
            margin-top: 0.35rem;
            padding-top: 0.75rem;
        }
        .invoice-total-row-balance {
            font-weight: 700;
            color: #b45309;
            border-top: 1px dashed #e2e8f0;
            margin-top: 0.35rem;
            padding-top: 0.75rem;
        }
        .invoice-footer {
            display: flex;
            justify-content: space-between;
            gap: 2rem;
            align-items: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 1.5rem;
        }
        .invoice-qr { text-align: center; }
        .invoice-qr svg { width: 110px; height: 110px; }
        .invoice-toolbar {
            max-width: 820px;
            margin: 1rem auto 0;
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }
        .invoice-toolbar button,
        .invoice-toolbar a {
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            padding: 0.55rem 1rem;
            text-decoration: none;
            color: #0f172a;
            cursor: pointer;
        }
        @media print {
            body { background: #fff; }
            .invoice-shell { margin: 0; box-shadow: none; border: 0; }
            .invoice-toolbar, .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="invoice-toolbar no-print">
        <button type="button" onclick="window.print()">Print A4</button>
        <a href="{{ route('admin.invoices.thermal', $invoice) }}" target="_blank">Thermal Receipt</a>
        <a href="{{ route('admin.invoices.pdf', $invoice) }}">Download PDF</a>
    </div>

    <div class="invoice-shell">
        <div class="invoice-body">
            @include('invoices.partials.header')
            @include('invoices.partials.customer')
            @include('invoices.partials.line-items')
            @include('invoices.partials.payment-summary')
            @include('invoices.partials.footer')
        </div>
    </div>
</body>
</html>
