<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceDocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceDocumentService $documents,
    ) {}

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
}
