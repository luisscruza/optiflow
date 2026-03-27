<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CompanyDetail;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

final class SharedQuotationPdfController
{
    public function __invoke(Quotation $quotation): Response
    {
        $quotation->load(['contact.primaryAddress', 'documentSubtype', 'items.product', 'items.tax', 'items.taxes', 'workspace']);

        $pdf = Pdf::loadView('quotations.pdf', [
            'quotation' => $quotation,
            'company' => CompanyDetail::getAll(),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("cotizacion-{$quotation->document_number}.pdf");
    }
}
