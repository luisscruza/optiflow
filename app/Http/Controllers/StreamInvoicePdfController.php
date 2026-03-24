<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CompanyDetail;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class StreamInvoicePdfController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Invoice $invoice): Response
    {
        $invoice->load(['contact.primaryAddress', 'documentSubtype', 'items.product', 'items.tax', 'items.taxes', 'payments', 'workspace']);

        activity()
            ->performedOn($invoice)
            ->causedBy(auth()->user())
            ->withProperties(['invoice_id' => $invoice->id])
            ->log('Descargó la factura en PDF');

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'company' => CompanyDetail::getAll(),
        ])->setPaper('a4', 'portrait');

        $filename = "factura-{$invoice->document_number}.pdf";

        return $pdf->stream($filename);
    }
}
