<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexInvoiceRequest;
use App\Models\Invoice;
use App\Services\InvoiceDocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceDocumentService $documents,
    ) {}

    public function index(IndexInvoiceRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.invoices.index', [
            'invoices' => $this->paginateInvoices($filters),
            'filters' => $filters,
        ]);
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        return view('admin.invoices.show', $this->documents->build($invoice));
    }

    public function print(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        return view('invoices.a4', $this->documents->build($invoice));
    }

    public function thermal(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        return view('invoices.thermal', $this->documents->build($invoice));
    }

    public function pdf(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $document = $this->documents->build($invoice);

        $pdf = Pdf::loadView('invoices.a4', $document)->setPaper('a4');

        return $pdf->download($invoice->invoice_number.'.pdf');
    }

    /**
     * @param  array{search?: string|null, type?: string|null, status?: string|null, from_date?: string|null, to_date?: string|null}  $filters
     * @return LengthAwarePaginator<Invoice>
     */
    private function paginateInvoices(array $filters): LengthAwarePaginator
    {
        return Invoice::query()
            ->with(['member', 'payments'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($nested) use ($search): void {
                    $nested->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('member', fn ($memberQuery) => $memberQuery->search($search));
                });
            })
            ->when(filled($filters['type'] ?? null), function ($query) use ($filters): void {
                $query->where('type', $filters['type']);
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
