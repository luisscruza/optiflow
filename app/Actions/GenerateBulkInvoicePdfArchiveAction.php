<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionNotFoundException;
use App\Models\CompanyDetail;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use ZipArchive;

final class GenerateBulkInvoicePdfArchiveAction
{
    /**
     * @param  array<int, int>  $ids
     * @return array{path: string, filename: string}
     */
    public function handle(array $ids): array
    {
        $invoices = Invoice::with([
            'contact',
            'documentSubtype',
            'items.product',
            'items.tax',
        ])->findMany($ids);

        if ($invoices->isEmpty()) {
            throw new ActionNotFoundException('No se encontraron facturas');
        }

        $company = CompanyDetail::getAll();
        $tempDir = storage_path('app/temp/bulk-invoices-'.uniqid());

        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $pdfFiles = [];

        try {
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

            $zipFilename = 'facturas-'.date('Y-m-d-His').'.zip';
            $zipPath = storage_path('app/temp/'.$zipFilename);

            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
                foreach ($pdfFiles as $file) {
                    $zip->addFile($file, basename($file));
                }
                $zip->close();
            }

            foreach ($pdfFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            if (file_exists($tempDir)) {
                rmdir($tempDir);
            }

            return [
                'path' => $zipPath,
                'filename' => $zipFilename,
            ];
        } catch (Exception $exception) {
            foreach ($pdfFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            if (file_exists($tempDir)) {
                rmdir($tempDir);
            }

            throw $exception;
        }
    }
}
