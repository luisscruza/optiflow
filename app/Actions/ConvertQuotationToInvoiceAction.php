<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\InvoiceResult;
use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class ConvertQuotationToInvoiceAction
{
    /**
     * Execute the action to convert a quotation to an invoice.
     *
     * @throws Throwable
     */
    public function handle(Workspace $workspace, Document $quotation, CreateInvoiceAction $createInvoiceAction): InvoiceResult
    {
        return DB::transaction(function () use ($workspace, $quotation, $createInvoiceAction) {
            // Prepare invoice data from quotation
            $invoiceData = [
                'contact_id' => $quotation->contact_id,
                'document_subtype_id' => $quotation->document_subtype_id,
                'ncf' => $quotation->documentSubtype->generateNCF(),
                'issue_date' => now()->format('Y-m-d'),
                'due_date' => $quotation->due_date,
                'payment_term' => $quotation->payment_term,
                'notes' => $quotation->notes ? "Convertida desde cotización #{$quotation->document_number}. ".$quotation->notes : "Convertida desde cotización #{$quotation->document_number}.",
                'subtotal' => $quotation->subtotal_amount,
                'discount_total' => $quotation->discount_amount,
                'tax_amount' => $quotation->tax_amount,
                'total' => $quotation->total_amount,
                'items' => [],
            ];

            // Convert quotation items to invoice items format
            foreach ($quotation->items as $index => $item) {
                $invoiceData['items'][] = [
                    'id' => 'new_'.$index, // Frontend needs IDs for new items
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_rate' => $item->discount_rate,
                    'discount_amount' => $item->discount_amount,
                    'tax_rate' => $item->tax_rate,
                    'tax_amount' => $item->tax_amount,
                    'total' => $item->total,
                ];
            }

            // Create the invoice using the CreateInvoiceAction
            $invoiceResult = $createInvoiceAction->handle($workspace, $invoiceData);

            if ($invoiceResult->isError()) {
                return $invoiceResult;
            }

            // Mark the quotation as converted
            $quotation->update(['status' => DocumentStatus::Converted]);

            return $invoiceResult;
        });
    }
}
