<?php

namespace App\Support;

use App\Models\Invoice;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class InvoiceQrCode
{
    public function svg(string $content, int $size = 120): string
    {
        return (string) QrCode::format('svg')
            ->size($size)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($content);
    }

    public function forInvoice(Invoice $invoice): string
    {
        return $this->svg(route('admin.invoices.show', $invoice, absolute: true));
    }
}
