@extends('layouts.admin', ['heading' => 'Receive Payment'])

@section('title', 'Receive Payment')

@section('content')
    <x-ui.page-header
        title="Receive Payment"
        subtitle="Collect payment for an invoice or record a POS sale"
    />

    <x-ui.card>
        <form
            action="{{ route('admin.payments.store') }}"
            method="POST"
            x-data="paymentReceive({
                mode: @js(old('mode', 'invoice')),
                invoices: @js($invoiceOptions),
                selectedInvoiceId: @js(old('invoice_id')),
                itemAmount: @js(old('item_amount')),
                discountAmount: @js(old('discount_amount', 0)),
                amountPaid: @js(old('amount_paid')),
                paymentType: @js(old('type')),
            })"
        >
            @csrf

            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item" role="presentation">
                    <button
                        type="button"
                        class="nav-link"
                        :class="{ active: mode === 'invoice' }"
                        @click="setMode('invoice')"
                    >
                        Invoice Payment
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button
                        type="button"
                        class="nav-link"
                        :class="{ active: mode === 'pos' }"
                        @click="setMode('pos')"
                    >
                        POS Sale
                    </button>
                </li>
            </ul>

            <input type="hidden" name="mode" x-model="mode">

            <div x-show="mode === 'invoice'" x-cloak>
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
                            :required="mode === 'invoice'"
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
                        <input type="text" class="form-control" :value="selectedInvoicePaymentTypeLabel" readonly>
                        <input type="hidden" name="type" x-model="paymentType">
                    </div>
                </div>

                <div class="card border mt-4" x-show="selectedInvoice">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-3">Invoice Details</h2>
                        <template x-if="selectedInvoice">
                            <div>
                                <dl class="row sg-profile-list mb-3">
                                    <dt class="col-sm-4">Invoice</dt>
                                    <dd class="col-sm-8" x-text="selectedInvoice.invoice_number"></dd>
                                    <dt class="col-sm-4">Member</dt>
                                    <dd class="col-sm-8" x-text="selectedInvoice.member_name ?? 'Walk-in'"></dd>
                                    <dt class="col-sm-4">Category</dt>
                                    <dd class="col-sm-8" x-text="selectedInvoice.type_label"></dd>
                                </dl>

                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th>Description</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="item in selectedInvoice.line_items" :key="item.description">
                                                <tr>
                                                    <td x-text="item.description"></td>
                                                    <td class="text-end" x-text="formatMoney(item.amount)"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>Subtotal</th>
                                                <th class="text-end" x-text="formatMoney(selectedInvoice.subtotal)"></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div x-show="mode === 'pos'" x-cloak>
                <div class="row g-3">
                    <div class="col-md-6">
                        <x-forms.searchable-select
                            label="Member (optional)"
                            name="member_id"
                            :options="$members->mapWithKeys(fn ($member) => [(string) $member->id => $member->name.' ('.$member->member_code.')'])->all()"
                            :selected="old('member_id')"
                            placeholder="Walk-in customer"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-forms.input
                            label="Item description"
                            name="description"
                            :value="old('description')"
                            placeholder="e.g. Protein shake"
                            x-bind:required="mode === 'pos'"
                        />
                    </div>
                    <div class="col-md-6">
                        <label for="item_amount" class="form-label">
                            Item amount <span class="text-danger">*</span>
                        </label>
                        <input
                            type="number"
                            name="item_amount"
                            id="item_amount"
                            x-model="itemAmount"
                            @input="syncPosPayment()"
                            step="0.01"
                            min="0.01"
                            @class(['form-control', 'is-invalid' => $errors->has('item_amount')])
                            :required="mode === 'pos'"
                        >
                        @error('item_amount')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <input type="hidden" name="type" value="{{ App\Enums\PaymentType::PosSale->value }}" x-show="mode === 'pos'">
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
                mode: config.mode,
                invoices: config.invoices,
                selectedInvoiceId: config.selectedInvoiceId ? String(config.selectedInvoiceId) : '',
                itemAmount: config.itemAmount ?? '',
                discountAmount: config.discountAmount ?? 0,
                amountPaid: config.amountPaid ?? '',
                paymentType: config.paymentType ?? '',
                currencySymbol: @js(App\Support\MoneyFormatter::symbol($gymCurrency)),
                paymentTypeLabels: @js(App\Enums\PaymentType::options()),

                init() {
                    if (this.mode === 'invoice') {
                        this.syncInvoicePayment();
                    } else {
                        this.syncPosPayment();
                    }
                },

                setMode(nextMode) {
                    this.mode = nextMode;

                    if (nextMode === 'invoice') {
                        this.syncInvoicePayment();
                    } else {
                        this.syncPosPayment();
                    }
                },

                get selectedInvoice() {
                    return this.invoices.find((invoice) => String(invoice.id) === String(this.selectedInvoiceId)) ?? null;
                },

                get selectedInvoicePaymentTypeLabel() {
                    if (! this.paymentType) {
                        return '—';
                    }

                    return this.paymentTypeLabels[this.paymentType] ?? this.paymentType;
                },

                get totalDue() {
                    if (this.mode === 'pos') {
                        const itemAmount = Number(this.itemAmount) || 0;
                        const discount = Number(this.discountAmount) || 0;

                        return Math.max(0, itemAmount - discount);
                    }

                    if (! this.selectedInvoice) {
                        return 0;
                    }

                    const discount = Number(this.discountAmount) || 0;

                    return Math.max(0, Number(this.selectedInvoice.subtotal) - discount);
                },

                syncInvoicePayment() {
                    if (! this.selectedInvoice) {
                        this.paymentType = '';
                        this.discountAmount = 0;
                        this.amountPaid = '';
                        return;
                    }

                    this.paymentType = this.selectedInvoice.payment_type;
                    this.discountAmount = Number(this.selectedInvoice.discount_amount) || 0;
                    this.syncAmountPaid();
                },

                syncPosPayment() {
                    this.syncAmountPaid();
                },

                syncAmountPaid() {
                    if (this.totalDue > 0) {
                        this.amountPaid = Number(this.totalDue).toFixed(2);
                    }
                },

                formatMoney(amount) {
                    return this.currencySymbol + Number(amount).toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                },
            }));
        });
    </script>
@endpush

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush
