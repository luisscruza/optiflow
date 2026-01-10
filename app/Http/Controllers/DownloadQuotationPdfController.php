<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class DownloadQuotationPdfController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Quotation $quotation): BinaryFileResponse
    {

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

        $filePath = storage_path("app/quotations/{$filename}");

        if (! file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
        $pdf->save($filePath);

        return response()->download($filePath, $filename)->deleteFileAfterSend(true);
    }
}
