<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\InvoiceResult;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentType;
use App\Enums\QuotationStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\DocumentSubtype;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Workspace;
use App\Support\NCFValidator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Throwable;

final readonly class CreateInvoiceAction
{
    public function __construct(
        private CreateInvoiceItemAction $createItems,
        private CreatePaymentAction $createPayment,
    ) {
        //
    }

    /**
     * @throws Throwable
     */
    public function handle(Workspace $workspace, array $data): InvoiceResult
    {
        return DB::transaction(function () use ($workspace, $data): InvoiceResult {

            $documentSubtype = DocumentSubtype::query()->findOrFail($data['document_subtype_id']);

            if (! NCFValidator::validate($data['ncf'], $documentSubtype, $data)) {
                return new InvoiceResult(error: 'El NCF proporcionado no es válido.');
            }

            $invoice = $this->createDocument($workspace, $data, $documentSubtype);

            if (! empty($data['quotation_id'])) {
                Quotation::query()
                    ->where('id', $data['quotation_id'])
                    ->update(['status' => QuotationStatus::Converted]);
            }

            $this->updateNumerator($documentSubtype, $data['ncf']);

            $items = array_filter($data['items'], fn (array $item): bool => isset($item['product_id'], $item['quantity'], $item['unit_price']) &&
                $item['quantity'] > 0);

            try {
                $this->createItems->handle($invoice, $items);
            } catch (InsufficientStockException $e) {
                DB::rollBack();

                return new InvoiceResult(
                    error: $e->getMessage(),
                );
            }

            // Attach salesmen if provided
            if (! empty($data['salesmen_ids']) && is_array($data['salesmen_ids'])) {
                $invoice->salesmen()->sync($data['salesmen_ids']);
            }

            // Create payment if payment data is provided
            if (! empty($data['register_payment']) && ! empty($data['payment_amount']) && $data['payment_amount'] > 0) {
                $this->createPayment->handle($invoice, [
                    'bank_account_id' => $data['payment_bank_account_id'],
                    'amount' => $data['payment_amount'],
                    'payment_date' => $data['payment_date'] ?? now()->toDateTimeString(),
                    'payment_method' => $data['payment_method'],
                    'payment_type' => PaymentType::InvoicePayment->value,
                    'notes' => $data['payment_notes'] ?? null,
                ]);
            }

            DB::afterCommit(function () use ($invoice, $workspace): void {
                Event::dispatch('invoice.created', [[
                    'invoice_id' => $invoice->id,
                    'workspace_id' => $workspace->id,
                    'user_id' => Auth::id(),
                ]]);
            });

            return new InvoiceResult(
                invoice: $invoice->load(['contact', 'documentSubtype', 'items.product'])
            );
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createDocument(Workspace $workspace, array $data, DocumentSubtype $documentSubtype): Invoice
    {
        return Invoice::query()->create([
            'workspace_id' => $workspace->id,
            'contact_id' => $data['contact_id'],
            'document_subtype_id' => $documentSubtype->id,
            'document_due_date' => $documentSubtype->valid_until_date ?? null,
            'status' => InvoiceStatus::PendingPayment,
            'document_number' => $data['ncf'],
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'],
            'currency_id' => 1, // TODO: Allow to switch currency...
            'total_amount' => $data['total'],
            'subtotal_amount' => $data['subtotal'],
            'discount_amount' => $data['discount_total'],
            'tax_amount' => $data['tax_amount'],
            'payment_term' => $data['payment_term'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     *  Updates the next number of the document type.
     */
    private function updateNumerator(DocumentSubtype $documentSubtype, string $ncf): void
    {
        $number = (int) mb_ltrim(mb_substr($ncf, mb_strlen($documentSubtype->prefix)), '0');

        if ($number >= $documentSubtype->next_number) {
            $documentSubtype->update(['next_number' => $number + 1]);
        }
    }
}
