<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\InvoiceResult;
use App\Exceptions\InsufficientStockException;
use App\Models\DocumentSubtype;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Workspace;
use App\Support\NCFValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class UpdateInvoiceAction
{
    public function __construct(
        private UpdateInvoiceItemAction $updateInvoiceItemAction
    ) {}

    /**
     * Update an existing invoice with all related data.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Workspace $workspace, Invoice $invoice, array $data): InvoiceResult
    {
        try {
            return DB::transaction(function () use ($invoice, $data): InvoiceResult {
                $originalItems = $invoice->items()->with('product')->get()->keyBy('id');

                $documentSubtype = DocumentSubtype::query()->findOrFail($data['document_subtype_id']);

                if (isset($data['ncf']) && $data['ncf'] !== $invoice->document_number) {
                    if (! NCFValidator::validate($data['ncf'], $documentSubtype, $data)) {
                        return new InvoiceResult(error: 'El NCF proporcionado no es válido.');
                    }
                }

                $this->updateDocumentFields($invoice, $data);

                // Only update numerator if the NCF actually changed
                if (isset($data['ncf']) && $data['ncf'] !== $invoice->document_number) {
                    $this->updateNumerator($documentSubtype, $data['ncf']);
                }

                $items = array_filter($data['items'] ?? [], fn (array $item): bool => isset($item['product_id'], $item['quantity'], $item['unit_price']) &&
                    $item['quantity'] > 0);

                try {
                    $this->processItemChanges($invoice, $originalItems, $items);
                } catch (InsufficientStockException $e) {
                    DB::rollBack();

                    return new InvoiceResult(error: $e->getMessage());
                }

                return new InvoiceResult($invoice->load(['contact', 'documentSubtype', 'items.product']));
            });
        } catch (Throwable $e) {
            Log::error('Failed to update invoice', [
                'invoice_id' => $invoice->id,
                'workspace_id' => $workspace->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new InvoiceResult(error: 'Error actualizando la factura: '.$e->getMessage());
        }
    }

    /**
     * Update document-level fields.
     *
     * @param  array<string, mixed>  $data
     */
    private function updateDocumentFields(Invoice $invoice, array $data): void
    {
        $updateData = [];

        if (isset($data['contact_id']) && $data['contact_id'] !== $invoice->contact_id) {
            $updateData['contact_id'] = $data['contact_id'];
        }

        if (isset($data['document_subtype_id']) && $data['document_subtype_id'] !== $invoice->document_subtype_id) {
            $updateData['document_subtype_id'] = $data['document_subtype_id'];
        }

        if (isset($data['ncf']) && $data['ncf'] !== $invoice->document_number) {
            $updateData['document_number'] = $data['ncf'];
        }

        if (isset($data['issue_date']) && $data['issue_date'] !== $invoice->issue_date->format('Y-m-d')) {
            $updateData['issue_date'] = $data['issue_date'];
        }

        if (isset($data['due_date']) && $data['due_date'] !== $invoice->due_date?->format('Y-m-d')) {
            $updateData['due_date'] = $data['due_date'];
        }

        if (isset($data['notes']) && $data['notes'] !== $invoice->notes) {
            $updateData['notes'] = $data['notes'];
        }

        if (isset($data['status']) && $data['status'] !== $invoice->status) {
            $updateData['status'] = $data['status'];
        }

        if (isset($data['total'])) {
            $updateData['total_amount'] = $data['total'];
        }

        if (isset($data['subtotal'])) {
            $updateData['subtotal_amount'] = $data['subtotal'];
        }

        if (isset($data['discount_total'])) {
            $updateData['discount_amount'] = $data['discount_total'];
        }

        if (isset($data['tax_amount'])) {
            $updateData['tax_amount'] = $data['tax_amount'];
        }

        if (isset($data['payment_term'])) {
            $updateData['payment_term'] = $data['payment_term'];
        }

        if ($updateData !== []) {
            $invoice->update($updateData);
        }
    }

    /**
     * Process all item changes (add, update, remove).
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, InvoiceItem>  $originalItems
     * @param  array<int, array<string, mixed>>  $newItems
     *
     * @throws InsufficientStockException
     */
    private function processItemChanges(
        Invoice $invoice,
        $originalItems,
        array $newItems
    ): void {
        $processedIds = [];

        foreach ($newItems as $itemData) {
            $itemId = $itemData['id'] ?? null;

            if ($itemId && isset($originalItems[$itemId])) {
                // Update existing item
                $originalItem = $originalItems[$itemId];
                $this->updateInvoiceItemAction->handle(
                    $invoice,
                    $originalItem,
                    $itemData
                );
                $processedIds[] = $itemId;
            } else {
                // Add new item (create through updateInvoiceItemAction with null item)
                $this->updateInvoiceItemAction->handle(
                    $invoice,
                    null, // null means create new
                    $itemData
                );
            }
        }

        // Remove items that are no longer present
        $itemsToRemove = $originalItems->reject(fn ($item): bool => in_array($item->id, $processedIds));

        foreach ($itemsToRemove as $item) {
            $this->removeInvoiceItem($invoice, $item);
        }
    }

    /**
     * Remove a invoice item and reverse its stock movements.
     */
    private function removeInvoiceItem(Invoice $invoice, InvoiceItem $item): void
    {
        $this->updateInvoiceItemAction->handle(
            $invoice,
            $item,
            ['remove' => true]
        );
    }

    /**
     * Updates the next number of the document type
     */
    private function updateNumerator(DocumentSubtype $documentSubtype, string $ncf): void
    {
        $number = (int) mb_ltrim(mb_substr($ncf, mb_strlen($documentSubtype->prefix)), '0');

        if ($number >= $documentSubtype->next_number) {
            $documentSubtype->update(['next_number' => $number + 1]);
        }
    }
}
