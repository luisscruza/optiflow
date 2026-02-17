<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CompanyDetail;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StreamInvoicePdfController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Invoice $invoice): Response
    {
        $invoice->load(['contact.primaryAddress', 'documentSubtype', 'items.product', 'items.tax', 'items.taxes', 'payments', 'workspace']);

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'company' => CompanyDetail::getAll(),
        ])->setPaper('a4', 'portrait');

        $filename = "factura-{$invoice->document_number}.pdf";

        return $pdf->stream($filename);
    }
}
