@extends('layouts.admin', ['heading' => 'Receive Payment'])

@section('title', 'Receive Payment')

@section('content')
    <x-ui.page-header
        title="Receive Payment"
        subtitle="Collect payment for an unpaid invoice"
    />

    <x-ui.card>
        <form
            action="{{ route('admin.payments.store') }}"
            method="POST"
            x-data="paymentReceive({
                invoices: @js($invoiceOptions),
                selectedInvoiceId: @js(old('invoice_id')),
                discountAmount: @js(old('discount_amount', 0)),
                amountPaid: @js(old('amount_paid')),
                paymentType: @js(old('type')),
            })"
        >
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="invoice_id" class="form-label">
                        Unpaid invoice <span class="text-danger">*</span>
                    </label>
                    <select
                        name="invoice_id"
                        id="invoice_id"
                        x-model="selectedInvoiceId"
                        @change="syncInvoicePayment()"
                        @class(['form-select', 'is-invalid' => $errors->has('invoice_id')])
                        required
                    >
                        <option value="">Select invoice</option>
                        @foreach ($invoiceOptions as $invoice)
                            <option value="{{ $invoice['id'] }}" @selected(old('invoice_id') == $invoice['id'])>
                                {{ $invoice['invoice_number'] }} — {{ $invoice['member_name'] ?? 'Walk-in' }}
                                ({{ App\Support\MoneyFormatter::format($invoice['total'], $gymCurrency) }})
                            </option>
                        @endforeach
                    </select>
                    @error('invoice_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Payment type</label>
                    <input type="text" class="form-control" :value="paymentTypeLabel" readonly>
                    <input type="hidden" name="type" x-model="paymentType">
                </div>

                <div class="col-12" x-show="selectedInvoice" x-cloak>
                    <div class="border rounded p-3 bg-light">
                        <h2 class="h6 fw-semibold mb-3">Invoice summary</h2>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="item in selectedInvoice?.line_items ?? []" :key="item.description">
                                        <tr>
                                            <td x-text="item.description"></td>
                                            <td class="text-end" x-text="formatMoney(item.amount)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Subtotal</th>
                                        <th class="text-end" x-text="formatMoney(selectedInvoice?.subtotal ?? 0)"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <h2 class="h6 fw-semibold mb-3">Payment Details</h2>
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="discount_amount" class="form-label">Discount</label>
                    <input
                        type="number"
                        name="discount_amount"
                        id="discount_amount"
                        x-model="discountAmount"
                        @input="syncAmountPaid()"
                        step="0.01"
                        min="0"
                        @class(['form-control', 'is-invalid' => $errors->has('discount_amount')])
                    >
                    @error('discount_amount')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Total due</label>
                    <input type="text" class="form-control" :value="formatMoney(totalDue)" readonly>
                </div>

                <div class="col-md-3">
                    <label for="amount_paid" class="form-label">
                        Amount paid <span class="text-danger">*</span>
                    </label>
                    <input
                        type="number"
                        name="amount_paid"
                        id="amount_paid"
                        x-model="amountPaid"
                        step="0.01"
                        min="0.01"
                        :max="totalDue > 0 ? totalDue : null"
                        @class(['form-control', 'is-invalid' => $errors->has('amount_paid')])
                        required
                    >
                    @error('amount_paid')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <x-forms.select
                        label="Payment method"
                        name="payment_method"
                        :options="$enabledPaymentMethods"
                        :selected="old('payment_method', App\Enums\PaymentMethod::Cash->value)"
                        required
                    />
                </div>

                <div class="col-md-6">
                    <x-forms.input
                        label="Reference"
                        name="payment_reference"
                        :value="old('payment_reference')"
                        placeholder="Optional transaction reference"
                    />
                </div>

                <div class="col-md-6">
                    <x-forms.textarea label="Notes" name="notes" rows="2" :value="old('notes')" />
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-4">
                <x-ui.button type="submit">Receive Payment</x-ui.button>
                <a href="{{ route('admin.payments.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('paymentReceive', (config) => ({
                invoices: config.invoices,
                selectedInvoiceId: config.selectedInvoiceId ? String(config.selectedInvoiceId) : '',
                discountAmount: config.discountAmount ?? 0,
                amountPaid: config.amountPaid ?? '',
                paymentType: config.paymentType ?? '',
                currencySymbol: @js(App\Support\MoneyFormatter::symbol($gymCurrency)),
                paymentTypeLabels: @js(App\Enums\PaymentType::options()),

                init() {
                    this.syncInvoicePayment();
                },

                get selectedInvoice() {
                    return this.invoices.find((invoice) => String(invoice.id) === this.selectedInvoiceId) ?? null;
                },

                get paymentTypeLabel() {
                    return this.paymentTypeLabels[this.paymentType] ?? '—';
                },

                get totalDue() {
                    const subtotal = Number(this.selectedInvoice?.subtotal ?? 0);
                    const discount = Number(this.discountAmount || 0);

                    return Math.max(0, subtotal - discount);
                },

                syncInvoicePayment() {
                    if (! this.selectedInvoice) {
                        this.paymentType = '';
                        this.amountPaid = '';
                        return;
                    }

                    this.paymentType = this.selectedInvoice.payment_type;
                    this.syncAmountPaid();
                },

                syncAmountPaid() {
                    this.amountPaid = this.totalDue > 0 ? this.totalDue.toFixed(2) : '';
                },

                formatMoney(amount) {
                    return `${this.currencySymbol}${Number(amount || 0).toFixed(2)}`;
                },
            }));
        });
    </script>
@endpush
