<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\PaymentRepositoryInterface;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentType;
use App\Exceptions\PaymentFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexPaymentRequest;
use App\Http\Requests\Admin\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Product;
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

        $unpaidInvoices = Invoice::query()
            ->with(['member', 'membershipPlan'])
            ->where('status', InvoiceStatus::Unpaid)
            ->latest('issued_at')
            ->limit(100)
            ->get();

        return view('admin.payments.create', [
            'unpaidInvoices' => $unpaidInvoices,
            'invoiceOptions' => $unpaidInvoices->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'member_name' => $invoice->member?->name,
                'member_code' => $invoice->member?->member_code,
                'plan_name' => $invoice->membershipPlan?->name,
                'type_label' => $invoice->type->label(),
                'payment_type' => $this->invoiceService->resolvePaymentType($invoice)->value,
                'subtotal' => (float) $invoice->subtotal,
                'discount_amount' => (float) $invoice->discount_amount,
                'total' => (float) $invoice->total,
                'line_items' => $invoice->line_items ?? [],
            ])->values()->all(),
            'members' => Member::query()->orderBy('name')->get(['id', 'name', 'member_code']),
        ]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $this->authorize('create', Payment::class);

        $data = $request->validated();

        try {
            if ($data['mode'] === 'pos') {
                $member = isset($data['member_id']) ? Member::query()->find($data['member_id']) : null;
                $discountAmount = (float) ($data['discount_amount'] ?? 0);
                $lineItems = $this->buildPosLineItems($data);

                $payment = $this->paymentService->receivePosSale(
                    $member,
                    $lineItems,
                    [
                        'type' => PaymentType::PosSale,
                        'amount_paid' => (float) $data['amount_paid'],
                        'payment_method' => $data['payment_method'],
                        'discount_amount' => $discountAmount,
                        'reference' => $data['payment_reference'] ?? null,
                        'notes' => $data['notes'] ?? null,
                    ],
                );
            } else {
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
                    'require_full_payment' => true,
                ]);
            }
        } catch (PaymentFailedException|InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['amount_paid' => $exception->getMessage()]);
        }

        Flash::success('Payment received successfully.');

        return redirect()->route('admin.payments.show', $payment);
    }

    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);

        $payment->load(['member', 'invoice.membershipPlan', 'invoice.membershipRenewal']);

        return view('admin.payments.show', [
            'payment' => $payment,
            'invoice' => $payment->invoice,
        ]);
    }

    public function receipt(Payment $payment): View
    {
        $this->authorize('view', $payment);

        $payment->load(['member', 'invoice.membershipPlan', 'invoice.membershipRenewal']);
        $invoice = $payment->invoice;
        $member = $payment->member;

        return view('admin.payments.receipt', [
            'payment' => $payment,
            'invoice' => $invoice,
            'member' => $member,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{product_id?: int, description: string, amount: float, quantity?: int, unit_price?: float}>
     */
    private function buildPosLineItems(array $data): array
    {
        if (! empty($data['product_id'])) {
            $product = Product::query()->findOrFail($data['product_id']);
            $quantity = (int) $data['quantity'];
            $unitPrice = (float) $product->selling_price;

            return [[
                'product_id' => $product->id,
                'description' => $product->name,
                'amount' => round($unitPrice * $quantity, 2),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
            ]];
        }

        return [[
            'description' => $data['description'],
            'amount' => (float) $data['item_amount'],
        ]];
    }
}
