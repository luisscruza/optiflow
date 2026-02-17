<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\CompanyDetail;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

final class GenerateInvoicePdfAction
{
    /**
     * @return array{path: string, filename: string}
     */
    public function handle(Invoice $invoice): array
    {
        $invoice->load([
            'contact',
            'documentSubtype',
            'items.product',
            'items.tax',
            'workspace',
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

        return [
            'path' => $filePath,
            'filename' => $filename,
        ];
    }
}
