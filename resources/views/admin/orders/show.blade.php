@extends('layouts.admin', ['heading' => 'Order'])

@section('title', $order->invoice_number)

@section('content')
    @php($summary = $document['summary'])

    <x-ui.page-header :title="$order->invoice_number" subtitle="POS order details and payment history">
        <x-slot:actions>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-light">All Orders</a>
            @can('create', App\Models\Payment::class)
                <a href="{{ route('admin.pos.index') }}" class="btn btn-light">New Sale</a>
            @endcan
            <a href="{{ route('admin.invoices.thermal', $order) }}" class="btn btn-light" target="_blank">Print Receipt</a>
            @if ($order->isOpen())
                <a href="#collect-payment" class="btn btn-primary">Collect Payment</a>
            @endif
            @can('delete', $order)
                <form
                    action="{{ route('admin.orders.destroy', $order) }}"
                    method="POST"
                    class="d-inline"
                    onsubmit="return confirm('Delete this order? Stock will be restored and all related payments will be removed.');"
                >
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">Delete Order</button>
                </form>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    @if (session('flash.message'))
        <div class="alert alert-{{ session('flash.type', 'success') }}">{{ session('flash.message') }}</div>
    @endif

    @if ($errors->has('order'))
        <div class="alert alert-danger">{{ $errors->first('order') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-xl-8">
            <x-ui.card title="Order Summary">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="small text-muted">Status</div>
                        <x-ui.badge :variant="match ($order->status) {
                            App\Enums\InvoiceStatus::Paid => 'success',
                            App\Enums\InvoiceStatus::Partial => 'warning',
                            App\Enums\InvoiceStatus::Unpaid => 'danger',
                            default => 'secondary',
                        }" class="mt-1">
                            {{ $order->status->label() }}
                        </x-ui.badge>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Customer</div>
                        <div class="fw-semibold mt-1">
                            @if ($order->member)
                                <a href="{{ route('admin.members.show', $order->member) }}">{{ $order->member->name }}</a>
                            @else
                                Walk-in customer
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Due date</div>
                        <div class="fw-semibold mt-1">{{ $order->due_at?->format('M j, Y') ?? 'Not set' }}</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Unit price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->line_items ?? [] as $item)
                                <tr>
                                    <td>{{ $item['description'] ?? 'Item' }}</td>
                                    <td class="text-end">{{ $item['quantity'] ?? 1 }}</td>
                                    <td class="text-end">{{ App\Support\MoneyFormatter::format($item['unit_price'] ?? $item['amount'], $gymCurrency) }}</td>
                                    <td class="text-end">{{ App\Support\MoneyFormatter::format($item['amount'], $gymCurrency) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end text-muted">Subtotal</td>
                                <td class="text-end">{{ App\Support\MoneyFormatter::format($summary['subtotal'], $gymCurrency) }}</td>
                            </tr>
                            @if ($summary['discount'] > 0)
                                <tr>
                                    <td colspan="3" class="text-end text-muted">Discount</td>
                                    <td class="text-end text-danger">-{{ App\Support\MoneyFormatter::format($summary['discount'], $gymCurrency) }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="3" class="text-end fw-semibold">Total</td>
                                <td class="text-end fw-semibold">{{ App\Support\MoneyFormatter::format($summary['total'], $gymCurrency) }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end text-muted">Paid</td>
                                <td class="text-end text-success">{{ App\Support\MoneyFormatter::format($summary['amount_paid'], $gymCurrency) }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end fw-semibold">Balance due</td>
                                <td class="text-end fw-bold {{ $summary['outstanding_balance'] > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ App\Support\MoneyFormatter::format($summary['outstanding_balance'], $gymCurrency) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-ui.card>

            <x-ui.card title="Payment History" class="mt-4">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Receipt</th>
                                <th>Method</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($order->payments as $payment)
                                <tr>
                                    <td class="text-muted">{{ $payment->paid_at->format('M j, Y g:i A') }}</td>
                                    <td>
                                        <a href="{{ route('admin.payments.show', $payment) }}">{{ $payment->receipt_number }}</a>
                                    </td>
                                    <td>{{ $payment->payment_method->label() }}</td>
                                    <td class="text-end fw-semibold">{{ App\Support\MoneyFormatter::format($payment->amount, $gymCurrency) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No payments recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>

        <div class="col-xl-4">
            @if ($order->isOpen())
                @can('create', App\Models\Payment::class)
                    <x-ui.card title="Collect Payment" class="mb-4" id="collect-payment">
                        <form action="{{ route('admin.orders.payments.store', $order) }}" method="POST">
                            @csrf

                            <div class="alert alert-light border mb-3">
                                Outstanding balance:
                                <strong class="text-danger">{{ App\Support\MoneyFormatter::format($summary['outstanding_balance'], $gymCurrency) }}</strong>
                            </div>

                            <div class="mb-3">
                                <label for="amount_paid" class="form-label">Amount received</label>
                                <input
                                    type="number"
                                    name="amount_paid"
                                    id="amount_paid"
                                    step="0.01"
                                    min="0.01"
                                    max="{{ $summary['outstanding_balance'] }}"
                                    value="{{ old('amount_paid', $summary['outstanding_balance']) }}"
                                    class="form-control @error('amount_paid') is-invalid @enderror"
                                    required
                                >
                                @error('amount_paid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Payment method</label>
                                <select name="payment_method" id="payment_method" class="form-select" required>
                                    @foreach ($enabledPaymentMethods as $value => $label)
                                        <option value="{{ $value }}" @selected(old('payment_method') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="payment_reference" class="form-label">Reference</label>
                                <input type="text" name="payment_reference" id="payment_reference" value="{{ old('payment_reference') }}" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea name="notes" id="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Record Payment</button>
                        </form>
                    </x-ui.card>
                @endcan
            @else
                <x-ui.card title="Order Complete" class="mb-4">
                    <p class="text-muted mb-3">This order has been fully paid.</p>
                    <a href="{{ route('admin.invoices.thermal', $order) }}" class="btn btn-outline-primary w-100 mb-2" target="_blank">Print Receipt</a>
                    <a href="{{ route('admin.pos.index') }}" class="btn btn-light w-100">Start New Sale</a>
                </x-ui.card>
            @endif
        </div>
    </div>
@endsection

@if (request()->boolean('print') && $order->isPaid())
    @push('scripts')
        <script>
            window.open(@js(route('admin.invoices.thermal', ['invoice' => $order, 'autoprint' => 1])), '_blank');
        </script>
    @endpush
@endif
