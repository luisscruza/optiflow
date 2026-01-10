<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CompanyDetail;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class DownloadInvoicePdfController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Invoice $invoice): BinaryFileResponse
    {
        $invoice->load([
            'contact',
            'documentSubtype',
            'items.product',
            'items.tax',
        ]);

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'company' => CompanyDetail::getAll(),
        ])->setPaper('a4', 'portrait');

        $filename = "factura-{$invoice->document_number}.pdf";

        $filePath = storage_path("app/invoices/{$filename}");

        if (! file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
        $pdf->save($filePath);

        return response()->download($filePath, $filename)->deleteFileAfterSend(true);
    }
}
