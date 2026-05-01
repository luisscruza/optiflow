<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\InvoiceResult;
use App\Enums\InvoiceStatus;
use App\Exceptions\EasyFactuException;
use App\Exceptions\InsufficientStockException;
use App\Models\DocumentSubtype;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Workspace;
use App\Services\EasyFactuService;
use App\Support\EasyFactuInvoiceMetadata;
use App\Support\EasyFactuPayloadTransformer;
use App\Support\NCFValidator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class UpdateInvoiceAction
{
    public function __construct(
        private UpdateInvoiceItemAction $updateInvoiceItemAction,
        private EasyFactuService $easyFactu,
    ) {}

    /**
     * Update an existing invoice with all related data.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Workspace $workspace, Invoice $invoice, array $data): InvoiceResult
    {
        // Block updates for electronic invoices that have been submitted or processed by DGII
        if ($invoice->isElectronic() && in_array($invoice->status, [
            InvoiceStatus::Submitted,
            InvoiceStatus::DgiiAccepted,
            InvoiceStatus::DgiiRejected,
        ])) {
            return new InvoiceResult(error: 'Esta factura electrónica ya fue emitida y no puede ser modificada.');
        }

        try {
            return DB::transaction(function () use ($invoice, $data): InvoiceResult {
                $originalItems = $invoice->items()->with('product')->get()->keyBy('id');

                $documentSubtype = DocumentSubtype::query()->findOrFail($data['document_subtype_id']);
                $isElectronic = $documentSubtype->is_electronic;

                if ((int) $data['document_subtype_id'] !== $invoice->document_subtype_id) {
                    return new InvoiceResult(error: 'No se puede cambiar el tipo de comprobante de una factura existente.');
                }

                // NCF validation: skip for electronic types (EasyFactu manages sequences)
                if (! $isElectronic && isset($data['ncf']) && $data['ncf'] !== $invoice->document_number) {
                    if (! NCFValidator::validate($data['ncf'], $documentSubtype, $data)) {
                        return new InvoiceResult(error: 'El NCF proporcionado no es válido.');
                    }
                }

                $this->updateDocumentFields($invoice, $data);

                // Only update numerator for non-electronic types when NCF actually changed
                if (! $isElectronic && isset($data['ncf']) && $data['ncf'] !== $invoice->document_number) {
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

                // Update salesmen if provided
                if (isset($data['salesmen_ids']) && is_array($data['salesmen_ids'])) {
                    $invoice->salesmen()->sync($data['salesmen_ids']);
                }

                // Sync changes with EasyFactu for electronic draft invoices
                if ($isElectronic && $invoice->isDraft() && $invoice->easyfactu_invoice_id) {
                    $this->syncWithEasyFactu($invoice);
                }

                DB::afterCommit(function () use ($invoice): void {
                    Event::dispatch('invoice.updated', [[
                        'invoice_id' => $invoice->id,
                        'workspace_id' => $invoice->workspace_id,
                        'user_id' => Auth::id(),
                    ]]);
                });

                return new InvoiceResult($invoice->load(['contact', 'documentSubtype', 'items.product']));
            });
        } catch (Throwable $e) {
            Log::error('Failed to update invoice', [
                'invoice_id' => $invoice->id,
                'workspace_id' => $invoice->workspace_id,
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

    /**
     * Sync the updated invoice with EasyFactu's draft.
     */
    private function syncWithEasyFactu(Invoice $invoice): void
    {
        try {
            $invoice->loadMissing(['contact', 'items.taxes', 'items.product']);
            $payload = EasyFactuPayloadTransformer::toUpdatePayload($invoice);
            $response = $this->easyFactu->updateDraftInvoice($invoice->easyfactu_invoice_id, $payload);

            $efInvoice = $response['invoice'] ?? [];

            // Update local fields with any changes from EasyFactu
            if (! empty($efInvoice['encf'])) {
                $invoice->update([
                    'encf' => $efInvoice['encf'],
                    'document_number' => $efInvoice['encf'],
                    'dgii_signed_at' => EasyFactuInvoiceMetadata::extractSignedAt($efInvoice) ?? $invoice->dgii_signed_at,
                ]);
            }
        } catch (EasyFactuException $e) {
            Log::error('Failed to sync draft update with EasyFactu', [
                'invoice_id' => $invoice->id,
                'easyfactu_invoice_id' => $invoice->easyfactu_invoice_id,
                'error' => $e->getMessage(),
                'errors' => $e->errors,
            ]);

            // Don't fail the local update — the user can retry later
            $invoice->update([
                'dgii_status' => 'Error sincronización: '.$e->getMessage(),
            ]);
        }
    }
}
