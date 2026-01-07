<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\InvoiceResult;
use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 *  TODO: This is partially wrong.
 *  We cannot just "convert" the quotation,
 *  because the invoice might need a different document subtype,
 *  and the NCF might need to be generated differently.
 */
final readonly class ConvertQuotationToInvoiceAction
{
    /**
     * Execute the action to convert a quotation to an invoice.
     *
     * @throws Throwable
     */
    public function handle(Workspace $workspace, Quotation $quotation, CreateInvoiceAction $createInvoiceAction): InvoiceResult
    {
        return DB::transaction(function () use ($workspace, $quotation, $createInvoiceAction): InvoiceResult {
            $invoiceData = [
                'contact_id' => $quotation->contact_id,
                'document_subtype_id' => $quotation->document_subtype_id,
                'ncf' => $quotation->documentSubtype->generateNCF(),
                'issue_date' => now()->format('Y-m-d'),
                'due_date' => $quotation->due_date,
                'payment_term' => 'manual',
                'notes' => $quotation->notes ? "Convertida desde cotización #{$quotation->document_number}. ".$quotation->notes : "Convertida desde cotización #{$quotation->document_number}.",
                'subtotal' => $quotation->subtotal_amount,
                'discount_total' => $quotation->discount_amount,
                'tax_amount' => $quotation->tax_amount,
                'total' => $quotation->total_amount,
                'items' => [],
            ];

            foreach ($quotation->items as $index => $item) {
                $invoiceData['items'][] = [
                    'id' => 'new_'.$index, 
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

            $invoiceResult = $createInvoiceAction->handle($workspace, $invoiceData);

            if ($invoiceResult->isError()) {
                return $invoiceResult;
            }

            $quotation->update(['status' => QuotationStatus::Converted]);

            return $invoiceResult;
        });
    }
}
