<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Product;
use App\Models\Tax;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class UpdateDocumentItemAction
{
    /**
     * Update, create, or remove a document item with proper stock management.
     *
     * @param  DocumentItem|null  $existingItem  null means create new item
     * @param  array<string, mixed>  $data
     *
     * @throws InsufficientStockException
     * @throws Throwable
     */
    public function handle(Document $document, ?DocumentItem $existingItem, array $data): void
    {
        DB::transaction(function () use ($document, $existingItem, $data) {
            if (isset($data['remove']) && $data['remove']) {
                // Remove item
                $this->removeItem($document, $existingItem);

                return;
            }

            if ($existingItem) {
                // Update existing item
                $this->updateItem($document, $existingItem, $data);
            } else {
                // Create new item (mimic CreateDocumentItemAction pattern)
                $this->createItem($document, $data);
            }
        });
    }

    /**
     * Create a new document item (following CreateDocumentItemAction pattern).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws InsufficientStockException
     * @throws Throwable
     */
    private function createItem(Document $document, array $data): void
    {
        $product = Product::findOrFail($data['product_id']);

        // Validate stock first (same as CreateDocumentItemAction)
        $this->validateStock($document, $data, $product);

        // Decrease stock (same as CreateDocumentItemAction)
        $this->decreaseStock($document, $data, $product);

        // Create the item line (same as CreateDocumentItemAction)
        $this->createLine($document, $data, $product);

        Log::info('Document item created via update', [
            'document_id' => $document->id,
            'product_id' => $product->id,
            'quantity' => $data['quantity'],
        ]);
    }

    /**
     * Update an existing document item.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws InsufficientStockException
     * @throws Throwable
     */
    private function updateItem(Document $document, DocumentItem $existingItem, array $data): void
    {
        $product = Product::findOrFail($data['product_id']);
        $originalQuantity = $existingItem->quantity;
        $newQuantity = $data['quantity'];

        // Handle stock reconciliation if quantity changed
        if ($product->track_stock && $originalQuantity !== $newQuantity) {
            $this->reconcileStockMovement($document, $product, $originalQuantity, $newQuantity);
        }

        // Update the item using the same pattern as CreateDocumentItemAction
        $taxId = $this->findTaxIdByRate($data['tax_rate'] ?? 0);

        $existingItem->update([
            'product_id' => $product->id,
            'description' => $data['description'] ?? null,
            'quantity' => $newQuantity,
            'unit_price' => $data['unit_price'],
            'discount_amount' => $data['discount_amount'] ?? 0,
            'discount_rate' => $data['discount_rate'] ?? 0,
            'tax_rate' => $data['tax_rate'] ?? 0,
            'tax_amount' => $data['tax_amount'] ?? 0,
            'tax_id' => $taxId,
            'total' => $data['total'],
        ]);

        Log::info('Document item updated', [
            'item_id' => $existingItem->id,
            'product_id' => $product->id,
            'original_quantity' => $originalQuantity,
            'new_quantity' => $newQuantity,
        ]);
    }

    /**
     * Remove a document item (find and delete the associated stock movement).
     */
    private function removeItem(Document $document, DocumentItem $item): void
    {
        $product = $item->product;
        $originalQuantity = $item->quantity;

        // Handle stock movement reversal for products that track stock
        if ($product && $product->track_stock) {
            // Find and delete the existing stock movement for this item
            $existingMovement = $document->stockMovements()
                ->where('product_id', $product->id)
                ->where('type', StockMovementType::SALE)
                ->first();

            if ($existingMovement) {
                // Return the stock to inventory
                $stockForWorkspace = $product->getStockForWorkspace($document->workspace);
                if ($stockForWorkspace) {
                    $stockForWorkspace->incrementStock($originalQuantity);
                }

                // Delete the stock movement
                $existingMovement->delete();
            }
        }

        // Delete the item
        $item->delete();

        Log::info('Document item removed', [
            'item_id' => $item->id,
            'product_id' => $product?->id,
            'original_quantity' => $originalQuantity,
        ]);
    }

    /**
     * Validate stock availability (same as CreateDocumentItemAction).
     */
    private function validateStock(Document $document, array $item, Product $product): void
    {
        if (! $product->hasSufficientStock($document->workspace_id, $item['quantity'])) {
            throw new InsufficientStockException('Insufficient stock for product: '.$product->name);
        }
    }

    /**
     * Decrease stock for new items (same as CreateDocumentItemAction).
     */
    private function decreaseStock(Document $document, array $item, Product $product): void
    {
        if (! $product->track_stock) {
            return;
        }

        $document->stockMovements()->create([
            'product_id' => $product->id,
            'workspace_id' => $document->workspace_id,
            'type' => StockMovementType::SALE,
            'quantity' => -$item['quantity'],
            'reference_number' => $document->document_number,
        ]);

        $stockForWorkspace = $product->getStockForWorkspace($document->workspace);

        if (! $stockForWorkspace) {
            throw new InsufficientStockException('No stock record found for product: '.$product->name);
        }

        $stockForWorkspace->decrementStock($item['quantity']);
    }

    /**
     * Create document line (same as CreateDocumentItemAction).
     */
    private function createLine(Document $document, array $item, Product $product): void
    {
        // Find tax_id based on tax_rate if not provided
        $taxId = $this->findTaxIdByRate($item['tax_rate'] ?? 0);

        $document->items()->create([
            'product_id' => $product->id,
            'description' => $item['description'] ?? null,
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'discount_amount' => $item['discount_amount'] ?? 0,
            'discount_rate' => $item['discount_rate'] ?? 0,
            'tax_rate' => $item['tax_rate'] ?? 0,
            'tax_amount' => $item['tax_amount'] ?? 0,
            'tax_id' => $taxId,
            'total' => $item['total'],
        ]);
    }

    /**
     * Reconcile stock movements when quantity changes.
     * Instead of creating new movements, update the existing movement.
     */
    private function reconcileStockMovement(
        Document $document,
        Product $product,
        float $originalQuantity,
        float $newQuantity
    ): void {
        $quantityDifference = $newQuantity - $originalQuantity;

        if ($quantityDifference === 0) {
            return; // No change needed
        }

        $stockForWorkspace = $product->getStockForWorkspace($document->workspace);

        if (! $stockForWorkspace) {
            throw new InsufficientStockException('No stock record found for product: '.$product->name);
        }

        // Find the existing stock movement for this document and product
        $existingMovement = $document->stockMovements()
            ->where('product_id', $product->id)
            ->where('type', StockMovementType::SALE)
            ->first();

        if (! $existingMovement) {
            // If no existing movement found, create a new one (fallback)
            $document->stockMovements()->create([
                'product_id' => $product->id,
                'workspace_id' => $document->workspace_id,
                'type' => StockMovementType::SALE,
                'quantity' => -$newQuantity,
                'reference_number' => $document->document_number,
            ]);

            // Adjust stock to match the new quantity
            if ($quantityDifference > 0) {
                // Need to decrease stock
                if (! $product->hasSufficientStock($document->workspace_id, $quantityDifference)) {
                    throw new InsufficientStockException("Stock insuficiente para incrementar la cantidad del producto {$product->name}.");
                }
                $stockForWorkspace->decrementStock($quantityDifference);
            } else {
                // Need to increase stock
                $stockForWorkspace->incrementStock(abs($quantityDifference));
            }

            return;
        }

        // Update the existing movement to reflect the new quantity
        $existingMovement->update([
            'quantity' => -$newQuantity, // Negative because it's a sale (outgoing)
        ]);

        // Adjust the actual stock based on the difference
        if ($quantityDifference > 0) {
            // Increased quantity - need to decrease more stock
            if (! $product->hasSufficientStock($document->workspace_id, $quantityDifference)) {
                throw new InsufficientStockException("Stock insuficiente para incrementar la cantidad del producto {$product->name}.");
            }
            $stockForWorkspace->decrementStock($quantityDifference);
        } else {
            // Decreased quantity - return stock
            $returnQuantity = abs($quantityDifference);
            $stockForWorkspace->incrementStock($returnQuantity);
        }
    }

    /**
     * Find tax ID by tax rate.
     */
    private function findTaxIdByRate(float $taxRate): int
    {
        $tax = Tax::where('rate', $taxRate)->first();

        if (! $tax) {
            // Fallback to default tax (usually the first one or create a default)
            $tax = Tax::first();
        }

        return $tax ? $tax->id : 1; // Ultimate fallback to ID 1
    }
}
