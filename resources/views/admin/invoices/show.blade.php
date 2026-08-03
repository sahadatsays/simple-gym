@extends('layouts.admin', ['heading' => 'Invoice'])

@section('title', $invoice->invoice_number)

@section('content')
    <x-ui.page-header
        :title="$invoice->invoice_number"
        :subtitle="$invoice->type->label().' invoice'"
    >
        <x-slot:actions>
            <a href="{{ route('admin.invoices.print', $invoice) }}" class="btn btn-light" target="_blank">Print A4</a>
            <a href="{{ route('admin.invoices.thermal', $invoice) }}" class="btn btn-light" target="_blank">Thermal Receipt</a>
            <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="btn btn-primary">Download PDF</a>
            @if ($payment)
                <a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-light">Payment Details</a>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-body p-0">
            <iframe
                src="{{ route('admin.invoices.print', $invoice) }}"
                title="Invoice preview"
                class="w-100 border-0"
                style="min-height: 980px; background: #f8fafc;"
            ></iframe>
        </div>
    </div>
@endsection
