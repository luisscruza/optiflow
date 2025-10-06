<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Tax;
use Illuminate\Support\Facades\DB;
use Throwable;

final class UpdateInvoiceItemAction
{
    /**
     * Update, create, or remove an invoice item with proper stock management.
     *
     * @param  InvoiceItem|null  $existingItem  null means create new item
     * @param  array<string, mixed>  $data
     *
     * @throws InsufficientStockException
     * @throws Throwable
     */
    public function handle(Invoice $invoice, ?InvoiceItem $existingItem, array $data): void
    {
        DB::transaction(function () use ($invoice, $existingItem, $data): void {
            if (isset($data['remove']) && $data['remove']) {
                // Remove item
                $this->removeItem($invoice, $existingItem);

                return;
            }

            if ($existingItem instanceof \App\Models\InvoiceItem) {
                // Update existing item
                $this->updateItem($invoice, $existingItem, $data);
            } else {
                $this->createItem($invoice, $data);
            }
        });
    }

    /**
     * Create a new invoice item.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws InsufficientStockException
     * @throws Throwable
     */
    private function createItem(Invoice $invoice, array $data): void
    {
        $product = Product::findOrFail($data['product_id']);

        $this->validateStock($invoice, $data, $product);
        $this->decreaseStock($invoice, $data, $product);
        $this->createLine($invoice, $data, $product);
    }

    /**
     * Update an existing invoice item.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws InsufficientStockException
     * @throws Throwable
     */
    private function updateItem(Invoice $invoice, InvoiceItem $existingItem, array $data): void
    {
        $product = Product::findOrFail($data['product_id']);
        $originalQuantity = $existingItem->quantity;
        $newQuantity = $data['quantity'];

        if ($product->track_stock && $originalQuantity !== $newQuantity) {
            $this->reconcileStockMovement($invoice, $product, $originalQuantity, $newQuantity);
        }

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
    }

    /**
     * Remove a invoice item (find and delete the associated stock movement).
     */
    private function removeItem(Invoice $invoice, InvoiceItem $item): void
    {
        $product = $item->product;
        $originalQuantity = $item->quantity;

        if ($product && $product->track_stock) {
            $existingMovement = $invoice->stockMovements()
                ->where('product_id', $product->id)
                ->where('type', StockMovementType::SALE)
                ->first();

            if ($existingMovement) {
                $stockForWorkspace = $product->getStockForWorkspace($invoice->workspace);
                if ($stockForWorkspace) {
                    $stockForWorkspace->incrementStock($originalQuantity);
                }

                $existingMovement->delete();
            }
        }

        $item->delete();
    }

    /**
     * Validate stock availability
     */
    private function validateStock(Invoice $invoice, array $item, Product $product): void
    {
        if (! $product->hasSufficientStock($invoice->workspace_id, $item['quantity'])) {
            throw new InsufficientStockException('Insufficient stock for product: '.$product->name);
        }
    }

    /**
     * Decrease stock for new items
     */
    private function decreaseStock(Invoice $invoice, array $item, Product $product): void
    {
        if (! $product->track_stock) {
            return;
        }

        $invoice->stockMovements()->create([
            'product_id' => $product->id,
            'workspace_id' => $invoice->workspace_id,
            'type' => StockMovementType::SALE,
            'quantity' => -$item['quantity'],
            'reference_number' => $invoice->document_number,
        ]);

        $stockForWorkspace = $product->getStockForWorkspace($invoice->workspace);

        if (!$stockForWorkspace instanceof \App\Models\ProductStock) {
            throw new InsufficientStockException('No stock record found for product: '.$product->name);
        }

        $stockForWorkspace->decrementStock($item['quantity']);
    }

    /**
     * Create document line
     */
    private function createLine(Invoice $invoice, array $item, Product $product): void
    {
        $taxId = $this->findTaxIdByRate($item['tax_rate'] ?? 0);

        $invoice->items()->create([
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
        Invoice $invoice,
        Product $product,
        float $originalQuantity,
        float $newQuantity
    ): void {
        $quantityDifference = $newQuantity - $originalQuantity;

        if ($quantityDifference === 0) {
            return; // No change needed
        }

        $stockForWorkspace = $product->getStockForWorkspace($invoice->workspace);

        if (!$stockForWorkspace instanceof \App\Models\ProductStock) {
            throw new InsufficientStockException('No stock record found for product: '.$product->name);
        }

        // Find the existing stock movement for this document and product
        $existingMovement = $invoice->stockMovements()
            ->where('product_id', $product->id)
            ->where('type', StockMovementType::SALE)
            ->first();

        if (! $existingMovement) {
            // If no existing movement found, create a new one (fallback)
            $invoice->stockMovements()->create([
                'product_id' => $product->id,
                'workspace_id' => $invoice->workspace_id,
                'type' => StockMovementType::SALE,
                'quantity' => -$newQuantity,
                'reference_number' => $invoice->document_number,
            ]);

            if ($quantityDifference > 0) {
                if (! $product->hasSufficientStock($invoice->workspace_id, $quantityDifference)) {
                    throw new InsufficientStockException("Stock insuficiente para incrementar la cantidad del producto {$product->name}.");
                }
                $stockForWorkspace->decrementStock($quantityDifference);
            } else {
                $stockForWorkspace->incrementStock(abs($quantityDifference));
            }

            return;
        }

        $existingMovement->update([
            'quantity' => -$newQuantity, // Negative because it's a sale (outgoing)
        ]);

        if ($quantityDifference > 0) {
            if (! $product->hasSufficientStock($invoice->workspace_id, $quantityDifference)) {
                throw new InsufficientStockException("Stock insuficiente para incrementar la cantidad del producto {$product->name}.");
            }
            $stockForWorkspace->decrementStock($quantityDifference);
        } else {
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
            $tax = Tax::first();
        }

        return $tax ? $tax->id : 1;
    }
}
