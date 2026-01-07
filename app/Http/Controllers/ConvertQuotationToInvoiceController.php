<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ConvertQuotationToInvoiceAction;
use App\Actions\CreateInvoiceAction;
use App\Enums\QuotationStatus;
use App\Models\Quotation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Session;
use Throwable;

final class ConvertQuotationToInvoiceController extends Controller
{
    /**
     * Convert a quotation to an invoice.
     */
    public function __invoke(
        Quotation $quotation,
        ConvertQuotationToInvoiceAction $convertAction,
        CreateInvoiceAction $createInvoiceAction
    ): RedirectResponse {
        if ($quotation->status === QuotationStatus::Converted->value) {
            return redirect()->back()
                ->with('error', 'La cotización ya ha sido convertida a factura.');
        }

        if ($quotation->status === QuotationStatus::Cancelled->value) {
            return redirect()->back()->with('error', 'No se puede convertir una cotización cancelada a factura.');
        }

        try {
            $workspace = Context::get('workspace');

            $quotation->load(['contact', 'documentSubtype', 'items.product']);

            $result = $convertAction->handle($workspace, $quotation, $createInvoiceAction);

            if ($result->isError()) {
                return redirect()->back()->with('error', "Error al convertir la cotización: " . $result->error);
            }

            Session::flash('success', "Cotización convertida exitosamente a factura #{$result->invoice->document_number}.");

            return redirect()->route('invoices.edit', $result->invoice);

        } catch (Throwable $e) {
            return redirect()->back()->with('error', 'Ocurrió un error al convertir la cotización a factura.');
        }
    }
}
