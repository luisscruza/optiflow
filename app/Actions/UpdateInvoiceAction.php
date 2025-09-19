<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\InvoiceResult;
use App\Exceptions\InsufficientStockException;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\DocumentSubtype;
use App\Models\Workspace;
use App\Support\NCFValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class UpdateInvoiceAction
{
    public function __construct(
        private readonly UpdateDocumentItemAction $updateDocumentItemAction
    ) {}

    /**
     * Update an existing invoice with all related data.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Workspace $workspace, Document $invoice, array $data): InvoiceResult
    {
        try {
            return DB::transaction(function () use ($workspace, $invoice, $data) {
                // Store original state for comparison
                $originalItems = $invoice->items()->with('product')->get()->keyBy('id');

                // Validate NCF if document number changed
                if (isset($data['ncf']) && $data['ncf'] !== $invoice->document_number) {
                    $documentSubtype = DocumentSubtype::findOrFail($data['document_subtype_id']);

                    if (! NCFValidator::validate($data['ncf'], $documentSubtype, $data)) {
                        return new InvoiceResult(error: 'El NCF proporcionado no es válido.');
                    }
                }

                // Update document-level fields
                $this->updateDocumentFields($invoice, $data);

                // Update numerator if NCF changed
                if (isset($data['ncf']) && $data['ncf'] !== $invoice->document_number) {
                    $documentSubtype = DocumentSubtype::findOrFail($data['document_subtype_id']);
                    $this->updateNumerator($documentSubtype, $data['ncf']);
                }

                // Filter valid items (same as CreateInvoiceAction)
                $items = array_filter($data['items'] ?? [], function ($item) {
                    return isset($item['product_id'], $item['quantity'], $item['unit_price']) &&
                        $item['quantity'] > 0;
                });

                // Process item changes
                try {
                    $this->processItemChanges($workspace, $invoice, $originalItems, $items);
                } catch (InsufficientStockException $e) {
                    DB::rollBack();

                    return new InvoiceResult(error: $e->getMessage());
                }

                // Recalculate document totals (let the model handle this)
                $invoice->recalculateTotal();

                Log::info('Invoice updated successfully', [
                    'invoice_id' => $invoice->id,
                    'workspace_id' => $workspace->id,
                ]);

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
    private function updateDocumentFields(Document $invoice, array $data): void
    {
        $updateData = [];

        // Only update fields that have changed
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

        // Update calculated totals (match CreateInvoiceAction pattern)
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

        if (! empty($updateData)) {
            $invoice->update($updateData);
        }
    }

    /**
     * Process all item changes (add, update, remove).
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, DocumentItem>  $originalItems
     * @param  array<int, array<string, mixed>>  $newItems
     *
     * @throws InsufficientStockException
     */
    private function processItemChanges(
        Workspace $workspace,
        Document $invoice,
        $originalItems,
        array $newItems
    ): void {
        $processedIds = [];

        // Process new and updated items
        foreach ($newItems as $itemData) {
            $itemId = $itemData['id'] ?? null;

            if ($itemId && isset($originalItems[$itemId])) {
                // Update existing item
                $originalItem = $originalItems[$itemId];
                $this->updateDocumentItemAction->handle(
                    $invoice,
                    $originalItem,
                    $itemData
                );
                $processedIds[] = $itemId;
            } else {
                // Add new item (create through UpdateDocumentItemAction with null item)
                $this->updateDocumentItemAction->handle(
                    $invoice,
                    null, // null means create new
                    $itemData
                );
            }
        }

        // Remove items that are no longer present
        $itemsToRemove = $originalItems->reject(function ($item) use ($processedIds) {
            return in_array($item->id, $processedIds);
        });

        foreach ($itemsToRemove as $item) {
            $this->removeDocumentItem($invoice, $item);
        }
    }

    /**
     * Remove a document item and reverse its stock movements.
     */
    private function removeDocumentItem(Document $invoice, DocumentItem $item): void
    {
        // Use the UpdateDocumentItemAction to handle removal with proper stock reconciliation
        $this->updateDocumentItemAction->handle(
            $invoice,
            $item,
            ['remove' => true]
        );
    }

    /**
     * Updates the next number of the document type (same as CreateInvoiceAction).
     */
    private function updateNumerator(DocumentSubtype $documentSubtype, string $ncf): void
    {
        $number = (int) ltrim(mb_substr($ncf, mb_strlen($documentSubtype->prefix)), '0');

        if ($number >= $documentSubtype->next_number) {
            $documentSubtype->update(['next_number' => $number + 1]);
        }
    }
}
