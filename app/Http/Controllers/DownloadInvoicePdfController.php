<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class DownloadInvoicePdfController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Document $invoice): Response
    {
        // Load necessary relationships
        $invoice->load([
            'contact',
            'documentSubtype',
            'items.product',
            'items.tax',
        ]);

        // Generate PDF
        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
        ])->setPaper('a4', 'portrait');

        // Generate filename
        $filename = "factura-{$invoice->document_number}.pdf";

        return $pdf->stream($filename);
    }
}
