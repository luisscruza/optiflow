<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Exception;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class DownloadProductImportTemplateController extends Controller
{
    /**
     * Download the import template.
     */
    public function __invoke(): BinaryFileResponse
    {
        $writer = new Writer();
        $tempFile = tempnam(sys_get_temp_dir(), 'product_import_template_');

        try {
            $writer->openToFile($tempFile);

            // Create header row using Row::fromValues()
            $headerRow = Row::fromValues([
                'Nombre',
                'SKU',
                'Descripción',
                'Precio',
                'Costo',
                'Controlar Stock',
                'Permitir Stock Negativo',
                'ID Impuesto por Defecto',
                'Categoría',
            ]);
            $writer->addRow($headerRow);

            // Add sample data row
            $sampleRow = Row::fromValues([
                'Producto de Ejemplo',
                'SKU-001',
                'Descripción del producto de ejemplo',
                '100.00',
                '60.00',
                'Sí',
                'No',
                '',
                'Categoría Ejemplo',
            ]);
            $writer->addRow($sampleRow);

            $writer->close();

            return response()->download($tempFile, 'plantilla-importacion-productos.xlsx')->deleteFileAfterSend(true);

        } catch (Exception $e) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            abort(500, 'Error generating template: '.$e->getMessage());
        }
    }
}
