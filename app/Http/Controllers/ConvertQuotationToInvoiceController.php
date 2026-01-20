<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use Illuminate\Http\RedirectResponse;

final class ConvertQuotationToInvoiceController
{
    /**
     * Redirect to the invoice creation page with quotation data pre-filled.
     */
    public function __invoke(Quotation $quotation): RedirectResponse
    {
        if ($quotation->status === QuotationStatus::Converted) {
            return redirect()->back()
                ->with('error', 'La cotización ya ha sido convertida a factura.');
        }

        if ($quotation->status === QuotationStatus::Cancelled) {
            return redirect()->back()
                ->with('error', 'No se puede convertir una cotización cancelada a factura.');
        }

        return redirect()->route('invoices.create-from-quotation', $quotation);
    }
}
