<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\PaymentRepositoryInterface;
use App\Enums\PaymentType;
use App\Exceptions\PaymentFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexPaymentRequest;
use App\Http\Requests\Admin\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentRepositoryInterface $payments,
        private PaymentService $paymentService,
        private InvoiceService $invoiceService,
    ) {}

    public function index(IndexPaymentRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.payments.index', [
            'payments' => $this->payments->paginateWithFilters($filters, config('gym.pagination.per_page')),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Payment::class);

        return view('admin.payments.create', [
            'invoiceOptions' => $this->invoiceService->unpaidInvoiceOptions(),
        ]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $invoice = Invoice::query()->findOrFail($data['invoice_id']);

            $payment = $this->paymentService->receiveForInvoice($invoice, [
                'member_id' => $invoice->member_id,
                'type' => isset($data['type'])
                    ? PaymentType::from($data['type'])
                    : $this->invoiceService->resolvePaymentType($invoice),
                'amount_paid' => (float) $data['amount_paid'],
                'payment_method' => $data['payment_method'],
                'discount_amount' => (float) ($data['discount_amount'] ?? 0),
                'reference' => $data['payment_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'require_full_payment' => false,
            ]);
        } catch (PaymentFailedException|InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['amount_paid' => $exception->getMessage()]);
        }

        Flash::success('Payment received successfully.');

        return redirect()->route('admin.payments.show', $payment);
    }

    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);

        $payment->load(['member', 'invoice.membershipPlan', 'invoice.membershipRenewal', 'invoice.payment']);

        if ($payment->invoice !== null) {
            $payment->invoice->setRelation('payment', $payment);
        }

        return view('admin.payments.show', [
            'payment' => $payment,
            'invoice' => $payment->invoice,
        ]);
    }

    public function receipt(Payment $payment): RedirectResponse
    {
        $this->authorize('view', $payment);

        $payment->load('invoice');

        abort_if($payment->invoice === null, 404);

        $params = ['invoice' => $payment->invoice];

        if (request()->boolean('pos')) {
            $params['autoprint'] = 1;
        }

        return redirect()->route('admin.invoices.thermal', $params);
    }
}
