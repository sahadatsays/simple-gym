<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentType;
use App\Exceptions\PaymentFailedException;
use App\Exceptions\PosOrderDeletionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexPosOrderRequest;
use App\Http\Requests\Admin\StorePosOrderPaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoiceDocumentService;
use App\Services\PaymentService;
use App\Support\Flash;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class PosOrderController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private InvoiceDocumentService $documents,
    ) {}

    public function index(IndexPosOrderRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.orders.index', [
            'orders' => $this->paginateOrders($filters),
            'filters' => $filters,
            'statusOptions' => InvoiceStatus::options(),
        ]);
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);
        abort_unless($invoice->isPosSale(), 404);

        $invoice->load(['member', 'payments', 'productSales.product']);

        return view('admin.orders.show', [
            'order' => $invoice,
            'document' => $this->documents->build($invoice),
        ]);
    }

    public function storePayment(StorePosOrderPaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('create', Payment::class);
        abort_unless($invoice->isPosSale() && $invoice->isOpen(), 404);

        $data = $request->validated();

        try {
            $this->paymentService->receiveForInvoice($invoice, [
                'member_id' => $invoice->member_id,
                'type' => PaymentType::PosSale,
                'amount_paid' => (float) $data['amount_paid'],
                'payment_method' => $data['payment_method'],
                'reference' => $data['payment_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'require_full_payment' => false,
            ]);
        } catch (PaymentFailedException|InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['amount_paid' => $exception->getMessage()]);
        }

        Flash::success('Payment recorded successfully.');

        return redirect()->route('admin.orders.show', $invoice);
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);
        abort_unless($invoice->isPosSale(), 404);

        try {
            $this->paymentService->deletePosOrder($invoice);
        } catch (PosOrderDeletionException $exception) {
            return back()->withErrors(['order' => $exception->getMessage()]);
        }

        Flash::success('Order deleted successfully. Stock and payments have been reversed.');

        return redirect()->route('admin.orders.index');
    }

    /**
     * @param  array{search?: string|null, status?: string|null, from_date?: string|null, to_date?: string|null}  $filters
     * @return LengthAwarePaginator<Invoice>
     */
    private function paginateOrders(array $filters): LengthAwarePaginator
    {
        return Invoice::query()
            ->with(['member', 'payments'])
            ->where('type', InvoiceType::PosSale)
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($nested) use ($search): void {
                    $nested->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('member', fn ($memberQuery) => $memberQuery->search($search));
                });
            })
            ->when(filled($filters['status'] ?? null), function ($query) use ($filters): void {
                $query->where('status', $filters['status']);
            })
            ->when(filled($filters['from_date'] ?? null), function ($query) use ($filters): void {
                $query->whereDate('issued_at', '>=', $filters['from_date']);
            })
            ->when(filled($filters['to_date'] ?? null), function ($query) use ($filters): void {
                $query->whereDate('issued_at', '<=', $filters['to_date']);
            })
            ->latest('issued_at')
            ->paginate(config('gym.pagination.per_page'))
            ->withQueryString();
    }
}
