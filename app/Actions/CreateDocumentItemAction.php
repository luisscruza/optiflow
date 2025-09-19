<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Document;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class CreateDocumentItemAction
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
    public function handle(Document $document, array $items): void
    {
        DB::transaction(function () use ($items, $document) {

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);

                $this->validateStock($document, $item, $product);
                $this->decreaseStock($document, $item, $product);
                $this->createLine($document, $item, $product);
            }
        });
    }

    /**
     * @throws InsufficientStockException
     */
    private function validateStock(Document $document, mixed $item, Product $product): void
    {
        if (! $product->hasSufficientStock($document->workspace_id, $item['quantity'])) {
            throw new InsufficientStockException('Insufficient stock for product: '.$product->name);
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
    private function createLine(Document $document, array $item, Product $product): void
    {
        $document->items()->create([
            'product_id' => $product->id,
            'description' => $item['description'] ?? null,
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'discount' => $item['discount_amount'] ?? 0,
            'tax_rate_snapshot' => $item['tax_rate'] ?? 0,
            'tax_id' => 1, // @TODO: Pass the tax ID from the request
            'tax_amount' => $item['tax_amount'] ?? 0,
            'total' => $item['total'],
        ]);
    }
}
