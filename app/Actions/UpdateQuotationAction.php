<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\QuotationResult;
use App\Models\DocumentSubtype;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Workspace;
use App\Support\NCFValidator;
use Illuminate\Support\Facades\DB;
use Throwable;

final class UpdateQuotationAction
{
    /**
     * Update an existing quotation with all related data (without stock movements).
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Workspace $workspace, Quotation $quotation, array $data): QuotationResult
    {
        try {
            return DB::transaction(function () use ($quotation, $data): QuotationResult {
                $originalItems = $quotation->items()->with('product')->get()->keyBy('id');

                if (isset($data['ncf']) && $data['ncf'] !== $quotation->document_number) {
                    $documentSubtype = DocumentSubtype::query()->findOrFail($data['document_subtype_id']);

                    if (! NCFValidator::validate($data['ncf'], $documentSubtype, $data)) {
                        return new QuotationResult(error: 'El NCF proporcionado no es válido.');
                    }
                }

                $this->updateDocumentFields($quotation, $data);

                if (isset($data['ncf']) && $data['ncf'] !== $quotation->document_number) {
                    $documentSubtype = DocumentSubtype::query()->findOrFail($data['document_subtype_id']);
                    $this->updateNumerator($documentSubtype, $data['ncf']);
                }

                $items = array_filter($data['items'] ?? [], fn (array $item): bool => isset($item['product_id'], $item['quantity'], $item['unit_price']) &&
                    $item['quantity'] > 0);

                $this->processItemChanges($quotation, $originalItems, $items);

                return new QuotationResult($quotation->load(['contact', 'documentSubtype', 'items.product']));
            });
        } catch (Throwable $e) {

            return new QuotationResult(error: 'Error actualizando la cotización: '.$e->getMessage());
        }
    }

    /**
     * Update document-level fields.
     *
     * @param  array<string, mixed>  $data
     */
    private function updateDocumentFields(Quotation $quotation, array $data): void
    {
        $updateData = [];

        // Only update fields that have changed
        if (isset($data['contact_id']) && $data['contact_id'] !== $quotation->contact_id) {
            $updateData['contact_id'] = $data['contact_id'];
        }

        if (isset($data['document_subtype_id']) && $data['document_subtype_id'] !== $quotation->document_subtype_id) {
            $updateData['document_subtype_id'] = $data['document_subtype_id'];
        }

        if (isset($data['ncf']) && $data['ncf'] !== $quotation->document_number) {
            $updateData['document_number'] = $data['ncf'];
        }

        if (isset($data['issue_date']) && $data['issue_date'] !== $quotation->issue_date->format('Y-m-d')) {
            $updateData['issue_date'] = $data['issue_date'];
        }

        if (isset($data['due_date']) && $data['due_date'] !== $quotation->due_date?->format('Y-m-d')) {
            $updateData['due_date'] = $data['due_date'];
        }

        if (isset($data['notes']) && $data['notes'] !== $quotation->notes) {
            $updateData['notes'] = $data['notes'];
        }

        if (isset($data['status']) && $data['status'] !== $quotation->status) {
            $updateData['status'] = $data['status'];
        }

        // Update calculated totals (match CreateQuotationAction pattern)
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
            $quotation->update($updateData);
        }
    }

    /**
     * Process all item changes (add, update, remove) without stock movements.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, QuotationItem>  $originalItems
     * @param  array<int, array<string, mixed>>  $newItems
     */
    private function processItemChanges(
        Quotation $quotation,
        $originalItems,
        array $newItems
    ): void {
        $processedIds = [];

        foreach ($newItems as $itemData) {
            $itemId = $itemData['id'] ?? null;

            if ($itemId && isset($originalItems[$itemId])) {
                $originalItem = $originalItems[$itemId];
                $this->updateQuotationItem($originalItem, $itemData);
                $processedIds[] = $itemId;
            } else {
                $this->createQuotationItem($quotation, $itemData);
            }
        }

        // Remove items that are no longer present
        $itemsToRemove = $originalItems->reject(fn ($item): bool => in_array($item->id, $processedIds));

        foreach ($itemsToRemove as $item) {
            $this->removeQuotationItem($item);
        }
    }

    /**
     * Create a new quotation item (without stock movements).
     *
     * @param  array<string, mixed>  $data
     */
    private function createQuotationItem(Quotation $quotation, array $data): void
    {
        $product = Product::query()->findOrFail($data['product_id']);

        $quotation->items()->create([
            'product_id' => $product->id,
            'description' => $data['description'] ?? null,
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
            'discount_amount' => $data['discount_amount'] ?? 0,
            'discount_rate' => $data['discount_rate'] ?? 0,
            'tax_rate' => $data['tax_rate'] ?? 0,
            'tax_amount' => $data['tax_amount'] ?? 0,
            'tax_id' => 1, // @TODO: Pass the tax ID from the request
            'total' => $data['total'],
        ]);
    }

    /**
     * Update an existing quotation item (without stock movements).
     *
     * @param  array<string, mixed>  $data
     */
    private function updateQuotationItem(QuotationItem $existingItem, array $data): void
    {
        $product = Product::query()->findOrFail($data['product_id']);

        $existingItem->update([
            'product_id' => $product->id,
            'description' => $data['description'] ?? null,
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
            'discount_amount' => $data['discount_amount'] ?? 0,
            'discount_rate' => $data['discount_rate'] ?? 0,
            'tax_rate' => $data['tax_rate'] ?? 0,
            'tax_amount' => $data['tax_amount'] ?? 0,
            'tax_id' => 1, // @TODO: Pass the tax ID from the request
            'total' => $data['total'],
        ]);
    }

    /**
     * Remove a quotation item (without stock movements).
     */
    private function removeQuotationItem(QuotationItem $item): void
    {
        $item->delete();
    }

    /**
     * Updates the next number of the document type (same as CreateQuotationAction).
     */
    private function updateNumerator(DocumentSubtype $documentSubtype, string $ncf): void
    {
        $number = (int) mb_ltrim(mb_substr($ncf, mb_strlen($documentSubtype->prefix)), '0');

        if ($number >= $documentSubtype->next_number) {
            $documentSubtype->update(['next_number' => $number + 1]);
        }
    }
}
