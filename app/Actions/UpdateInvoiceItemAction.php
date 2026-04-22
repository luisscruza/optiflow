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

final readonly class UpdateInvoiceItemAction
{
    public function __construct(private ApplyInventoryMovementAction $applyInventoryMovementAction) {}

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

            if ($existingItem instanceof InvoiceItem) {
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
        $product = Product::query()->findOrFail($data['product_id']);

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
        $existingProduct = Product::query()->findOrFail($existingItem->product_id);
        $product = Product::query()->findOrFail($data['product_id']);
        $originalQuantity = (float) $existingItem->quantity;
        $newQuantity = (float) $data['quantity'];

        if ($existingProduct->id !== $product->id) {
            $this->restoreStock($invoice, $existingProduct, $originalQuantity, 'Reverso por cambio de producto en factura');
            $this->validateStock($invoice, ['quantity' => $newQuantity], $product);
            $this->decreaseStock($invoice, ['quantity' => $newQuantity], $product);
        } elseif ($product->track_stock && $originalQuantity !== $newQuantity) {
            $this->reconcileStockMovement($invoice, $product, $originalQuantity, $newQuantity);
        }

        $taxId = $this->findTaxIdByRate($data['tax_rate'] ?? 0);

        $existingItem->update([
            'product_id' => $product->id,
            'description' => $data['description'] ?? null,
            'quantity' => $newQuantity,
            'unit_price' => $data['unit_price'],
            'subtotal' => $newQuantity * $data['unit_price'],
            'discount_amount' => $data['discount_amount'] ?? 0,
            'discount_rate' => $data['discount_rate'] ?? 0,
            'tax_rate' => $data['tax_rate'] ?? 0,
            'tax_amount' => $data['tax_amount'] ?? 0,
            'tax_id' => $taxId,
            'total' => $data['total'],
        ]);

        // Sync multi-tax relationship if taxes array is provided
        if (isset($data['taxes']) && is_array($data['taxes'])) {
            $taxesData = [];
            foreach ($data['taxes'] as $tax) {
                $taxesData[$tax['id']] = [
                    'rate' => $tax['rate'],
                    'amount' => $tax['amount'],
                ];
            }
            $existingItem->taxes()->sync($taxesData);
        }
    }

    /**
     * Remove a invoice item and append the inverse movement.
     */
    private function removeItem(Invoice $invoice, InvoiceItem $item): void
    {
        $product = $item->product;
        $originalQuantity = $item->quantity;

        if ($product && $product->track_stock) {
            $this->restoreStock($invoice, $product, (float) $originalQuantity, 'Reverso por eliminacion de item de factura');
        }

        // Detach all taxes before deleting the item
        $item->taxes()->detach();

        $item->delete();
    }

    /**
     * Validate stock availability
     *
     * @param  array<string, mixed>  $item
     */
    private function validateStock(Invoice $invoice, array $item, Product $product): void
    {
        if (! $product->hasSufficientStock($invoice->workspace_id, $item['quantity'])) {
            throw new InsufficientStockException('No hay stock ('.$item['quantity'].') suficiente para el producto: '.$product->name);
        }
    }

    /**
     * Decrease stock for new items
     *
     * @param  array<string, mixed>  $item
     */
    private function decreaseStock(Invoice $invoice, array $item, Product $product): void
    {
        if (! $product->track_stock) {
            return;
        }

        $this->applyInventoryMovementAction->handle($product, [
            'workspace_id' => $invoice->workspace_id,
            'quantity' => -abs((float) $item['quantity']),
            'type' => StockMovementType::SALE,
            'related_invoice_id' => $invoice->id,
            'reference_number' => $invoice->document_number,
            'note' => 'Salida por factura '.$invoice->document_number,
        ]);
    }

    /**
     * Create document line
     *
     * @param  array<string, mixed>  $item
     */
    private function createLine(Invoice $invoice, array $item, Product $product): void
    {
        $taxId = $this->findTaxIdByRate($item['tax_rate'] ?? 0);

        /** @var InvoiceItem $invoiceItem */
        $invoiceItem = $invoice->items()->create([
            'product_id' => $product->id,
            'description' => $item['description'] ?? null,
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'subtotal' => $item['quantity'] * $item['unit_price'],
            'discount_amount' => $item['discount_amount'] ?? 0,
            'discount_rate' => $item['discount_rate'] ?? 0,
            'tax_rate' => $item['tax_rate'] ?? 0,
            'tax_amount' => $item['tax_amount'] ?? 0,
            'tax_id' => $taxId,
            'total' => $item['total'],
        ]);

        // Sync multi-tax relationship if taxes array is provided
        if (! empty($item['taxes'])) {
            $taxesData = [];
            foreach ($item['taxes'] as $tax) {
                $taxesData[$tax['id']] = [
                    'rate' => $tax['rate'],
                    'amount' => $tax['amount'],
                ];
            }
            $invoiceItem->taxes()->sync($taxesData);
        }
    }

    /**
     * Reconcile stock movements when quantity changes using append-only movements.
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

        if ($quantityDifference > 0) {
            if (! $product->hasSufficientStock($invoice->workspace_id, $quantityDifference)) {
                throw new InsufficientStockException("Stock insuficiente para incrementar la cantidad del producto {$product->name}.");
            }

            $this->applyInventoryMovementAction->handle($product, [
                'workspace_id' => $invoice->workspace_id,
                'quantity' => -abs($quantityDifference),
                'type' => StockMovementType::SALE,
                'related_invoice_id' => $invoice->id,
                'reference_number' => $invoice->document_number,
                'note' => 'Salida adicional por actualizacion de factura '.$invoice->document_number,
            ]);
        } else {
            $this->restoreStock($invoice, $product, abs($quantityDifference), 'Reverso parcial por actualizacion de factura');
        }
    }

    private function restoreStock(Invoice $invoice, Product $product, float $quantity, string $reason): void
    {
        if (! $product->track_stock || $quantity <= 0) {
            return;
        }

        $this->applyInventoryMovementAction->handle($product, [
            'workspace_id' => $invoice->workspace_id,
            'quantity' => abs($quantity),
            'type' => StockMovementType::RETURN_IN,
            'related_invoice_id' => $invoice->id,
            'reference_number' => $invoice->document_number,
            'note' => $reason.' '.$invoice->document_number,
        ]);
    }

    /**
     * Find tax ID by tax rate.
     */
    private function findTaxIdByRate(float $taxRate): int
    {
        $tax = Tax::query()->where('rate', $taxRate)->first();

        if (! $tax) {
            $tax = Tax::query()->first();
        }

        return $tax ? $tax->id : 1;
    }
}
