<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class DownloadQuotationPdfController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Document $quotation): Response
    {
        // Load necessary relationships
        $quotation->load([
            'contact',
            'documentSubtype',
            'items.product',
            'items.tax',
        ]);

        // Generate PDF
        $pdf = Pdf::loadView('quotations.pdf', [
            'quotation' => $quotation,
        ])->setPaper('a4', 'portrait');

        // Generate filename
        $filename = "cotizacion-{$quotation->document_number}.pdf";

        return $pdf->stream($filename);
    }
}
