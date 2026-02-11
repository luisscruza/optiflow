<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionNotFoundException;
use App\Models\CompanyDetail;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use ZipArchive;

final class GenerateBulkQuotationPdfArchiveAction
{
    /**
     * @param  array<int, int>  $ids
     * @return array{path: string, filename: string}
     */
    public function handle(array $ids): array
    {
        $quotations = Quotation::with([
            'contact.primaryAddress',
            'documentSubtype',
            'items.product',
            'items.tax',
            'items.taxes',
        ])->findMany($ids);

        if ($quotations->isEmpty()) {
            throw new ActionNotFoundException('No se encontraron cotizaciones');
        }

        $company = CompanyDetail::getAll();
        $tempDir = storage_path('app/temp/bulk-quotations-'.uniqid());
        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $pdfFiles = [];

        try {
            foreach ($quotations as $quotation) {
                $pdf = Pdf::loadView('quotations.pdf', [
                    'quotation' => $quotation,
                    'company' => $company,
                ])->setPaper('a4', 'portrait');

                $filename = "cotizacion-{$quotation->document_number}.pdf";
                $filePath = $tempDir.'/'.$filename;
                $pdf->save($filePath);
                $pdfFiles[] = $filePath;
            }

            $zipFilename = 'cotizaciones-'.date('Y-m-d-His').'.zip';
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
