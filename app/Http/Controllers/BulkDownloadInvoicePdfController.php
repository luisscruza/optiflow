<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CompanyDetail;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use ZipArchive;

final class BulkDownloadInvoicePdfController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'max:10'],
            'ids.*' => ['required', 'integer', 'exists:invoices,id'],
        ]);

        $invoices = Invoice::with([
            'contact',
            'documentSubtype',
            'items.product',
            'items.tax',
        ])->findMany($validated['ids']);

        if ($invoices->isEmpty()) {
            abort(404, 'No se encontraron facturas');
        }

        $company = CompanyDetail::getAll();
        $tempDir = storage_path('app/temp/bulk-invoices-'.uniqid());

        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $pdfFiles = [];

        try {
            // Generate PDFs
            foreach ($invoices as $invoice) {
                $pdf = Pdf::loadView('invoices.pdf', [
                    'invoice' => $invoice,
                    'company' => $company,
                ])->setPaper('a4', 'portrait');

                $filename = "factura-{$invoice->document_number}.pdf";
                $filePath = $tempDir.'/'.$filename;
                $pdf->save($filePath);
                $pdfFiles[] = $filePath;
            }

            // Create ZIP
            $zipFilename = 'facturas-'.date('Y-m-d-His').'.zip';
            $zipPath = storage_path('app/temp/'.$zipFilename);

            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
                foreach ($pdfFiles as $file) {
                    $zip->addFile($file, basename($file));
                }
                $zip->close();
            }

            // Clean up individual PDFs
            foreach ($pdfFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            // Remove temp directory
            if (file_exists($tempDir)) {
                rmdir($tempDir);
            }

            return response()->download($zipPath, $zipFilename)->deleteFileAfterSend(true);
        } catch (Exception $e) {
            // Clean up on error
            foreach ($pdfFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            if (file_exists($tempDir)) {
                rmdir($tempDir);
            }

            throw $e;
        }
    }
}
