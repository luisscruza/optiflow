<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\CompanyDetail;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;

final class GenerateQuotationPdfAction
{
    /**
     * @return array{path: string, filename: string}
     */
    public function handle(Quotation $quotation): array
    {
        $quotation->load([
            'contact.primaryAddress',
            'documentSubtype',
            'items.product',
            'items.tax',
            'items.taxes',
        ]);

        $pdf = Pdf::loadView('quotations.pdf', [
            'quotation' => $quotation,
            'company' => CompanyDetail::getAll(),
        ])->setPaper('a4', 'portrait');

        $filename = "cotizacion-{$quotation->document_number}.pdf";
        $filePath = storage_path("app/quotations/{$filename}");

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
