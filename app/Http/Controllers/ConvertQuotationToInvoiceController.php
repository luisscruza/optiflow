<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ConvertQuotationToInvoiceAction;
use App\Actions\CreateInvoiceAction;
use App\Enums\DocumentStatus;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Throwable;

final class ConvertQuotationToInvoiceController extends Controller
{
    /**
     * Convert a quotation to an invoice.
     */
    public function __invoke(
        Document $quotation,
        ConvertQuotationToInvoiceAction $convertAction,
        CreateInvoiceAction $createInvoiceAction
    ): RedirectResponse {
        // Validate that this is a quotation
        if ($quotation->type !== 'quotation') {
            return redirect()->back()
                ->withErrors(['error' => 'El documento seleccionado no es una cotización.']);
        }

        // Validate quotation status
        if ($quotation->status === DocumentStatus::Converted->value) {
            return redirect()->back()
                ->withErrors(['error' => 'Esta cotización ya ha sido convertida a factura.']);
        }

        if ($quotation->status === DocumentStatus::Cancelled->value) {
            return redirect()->back()
                ->withErrors(['error' => 'No se puede convertir una cotización cancelada.']);
        }

        try {
            $workspace = Context::get('workspace');

            // Load quotation with all necessary relationships
            $quotation->load(['contact', 'documentSubtype', 'items.product']);

            // Use the action to convert quotation to invoice
            $result = $convertAction->handle($workspace, $quotation, $createInvoiceAction);

            if ($result->isError()) {
                return redirect()->back()
                    ->withErrors(['error' => 'Error al crear la factura: '.$result->error]);
            }

            Log::info('Quotation converted to invoice', [
                'quotation_id' => $quotation->id,
                'invoice_id' => $result->invoice->id,
                'workspace_id' => $workspace->id,
            ]);

            Session::flash('success', "Cotización convertida exitosamente a factura #{$result->invoice->document_number}.");

            return redirect()->route('invoices.show', $result->invoice);

        } catch (Throwable $e) {
            Log::error('Failed to convert quotation to invoice', [
                'quotation_id' => $quotation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Error al convertir la cotización: '.$e->getMessage()]);
        }
    }
}
