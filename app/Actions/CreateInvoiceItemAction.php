<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class CreateInvoiceItemAction
{
    /**
     * Execute the action.
     *
     * @param array<int, array{
     *     product_id: int,
     *     description?: string,
     *     quantity: float,
     *     unit_price: float,
     *     discount_rate?: float,
     *     discount_amount?: float,
     *     tax_rate?: float,
     *     tax_amount?: float,
     *     total?: float,
     * }> $items
     *
     * @throws Throwable
     */
    public function handle(Invoice $invoice, array $items): void
    {
        DB::transaction(function () use ($items, $invoice): void {

            foreach ($items as $item) {
                $product = Product::query()->findOrFail($item['product_id']);

                $this->validateStock($invoice, $item, $product);
                $this->decreaseStock($invoice, $item, $product);
                $this->createLine($invoice, $item, $product);
            }
        });
    }

    /**
     * @throws InsufficientStockException
     */
    private function validateStock(Invoice $invoice, mixed $item, Product $product): void
    {
        if (! $product->hasSufficientStock($invoice->workspace_id, $item['quantity'])) {
            throw new InsufficientStockException('No hay stock (' . $item['quantity'] . ') suficiente para el producto: '.$product->name);
        }
    }

    /**
     * Creates the document lines.
     *
     * @param array{
     *     product_id: int,
     *     quantity: float,
     *     unit_price: float,
     *     discount_rate?: float,
     *     discount_amount?: float,
     *     tax_rate?: float,
     *     tax_amount?: float,
     *     total: float,
     * } $item
     *
     * @throws InsufficientStockException
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

        if (! $stockForWorkspace instanceof \App\Models\ProductStock) {
            throw new InsufficientStockException('No stock record found for product: '.$product->name);
        }

        $stockForWorkspace->decrementStock($item['quantity']);
    }

    /**
     * Creates the document lines.
     *
     * @param array{
     *     product_id: int,
     *     description?: string,
     *     quantity: float,
     *     unit_price: float,
     *     discount_rate?: float,
     *     discount_amount?: float,
     *     tax_rate?: float,
     *     tax_amount?: float,
     *     total: float,
     * } $item
     */
    private function createLine(Invoice $invoice, array $item, Product $product): void
    {
        $invoice->items()->create([
            'product_id' => $product->id,
            'description' => $item['description'] ?? null,
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'discount_amount' => $item['discount_amount'] ?? 0,
            'discount_rate' => $item['discount_rate'] ?? 0,
            'tax_rate' => $item['tax_rate'] ?? 0,
            'tax_amount' => $item['tax_amount'] ?? 0,
            'tax_id' => 1, // @TODO: Pass the tax ID from the request
            'total' => $item['total'],
        ]);
    }
}
