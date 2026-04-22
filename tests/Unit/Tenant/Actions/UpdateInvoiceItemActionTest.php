<?php

declare(strict_types=1);

use App\Actions\UpdateInvoiceItemAction;
use App\Enums\StockMovementType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\Workspace;

test('updating an invoice item appends a new stock movement with balances', function (): void {
    $workspace = Workspace::factory()->create();
    $product = Product::factory()->tracksStock()->create();
    $invoice = Invoice::factory()->create([
        'workspace_id' => $workspace->id,
        'document_number' => 'B0100000001',
    ]);

    ProductStock::factory()->forProductAndWorkspace($product, $workspace)->withQuantity(7)->create();

    $invoice->stockMovements()->create([
        'product_id' => $product->id,
        'workspace_id' => $workspace->id,
        'type' => StockMovementType::SALE,
        'quantity' => -3,
        'previous_quantity' => 10,
        'current_quantity' => 7,
        'reference_number' => $invoice->document_number,
    ]);

    $item = InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $product->id,
        'quantity' => 3,
        'unit_price' => 100,
        'total' => 300,
    ]);

    app(UpdateInvoiceItemAction::class)->handle($invoice, $item, [
        'product_id' => $product->id,
        'quantity' => 5,
        'unit_price' => 100,
        'tax_rate' => 0,
        'tax_amount' => 0,
        'total' => 500,
    ]);

    expect($item->refresh()->quantity)->toBe('5.00')
        ->and($product->getStockQuantityForWorkspace($workspace))->toBe(5.0);

    $movements = StockMovement::query()
        ->where('related_invoice_id', $invoice->id)
        ->where('product_id', $product->id)
        ->orderBy('id')
        ->get();

    expect($movements)->toHaveCount(2)
        ->and((float) $movements[0]->quantity)->toBe(-3.0)
        ->and((float) $movements[0]->previous_quantity)->toBe(10.0)
        ->and((float) $movements[0]->current_quantity)->toBe(7.0)
        ->and((float) $movements[1]->quantity)->toBe(-2.0)
        ->and((float) $movements[1]->previous_quantity)->toBe(7.0)
        ->and((float) $movements[1]->current_quantity)->toBe(5.0);
});

test('removing an invoice item appends a return movement instead of deleting history', function (): void {
    $workspace = Workspace::factory()->create();
    $product = Product::factory()->tracksStock()->create();
    $invoice = Invoice::factory()->create([
        'workspace_id' => $workspace->id,
        'document_number' => 'B0100000002',
    ]);

    ProductStock::factory()->forProductAndWorkspace($product, $workspace)->withQuantity(7)->create();

    $invoice->stockMovements()->create([
        'product_id' => $product->id,
        'workspace_id' => $workspace->id,
        'type' => StockMovementType::SALE,
        'quantity' => -3,
        'previous_quantity' => 10,
        'current_quantity' => 7,
        'reference_number' => $invoice->document_number,
    ]);

    $item = InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $product->id,
        'quantity' => 3,
        'unit_price' => 100,
        'total' => 300,
    ]);

    app(UpdateInvoiceItemAction::class)->handle($invoice, $item, [
        'remove' => true,
    ]);

    expect(InvoiceItem::query()->find($item->id))->toBeNull()
        ->and($product->getStockQuantityForWorkspace($workspace))->toBe(10.0);

    $movements = StockMovement::query()
        ->where('related_invoice_id', $invoice->id)
        ->where('product_id', $product->id)
        ->orderBy('id')
        ->get();

    expect($movements)->toHaveCount(2)
        ->and($movements[1]->type)->toBe(StockMovementType::RETURN_IN)
        ->and((float) $movements[1]->quantity)->toBe(3.0)
        ->and((float) $movements[1]->previous_quantity)->toBe(7.0)
        ->and((float) $movements[1]->current_quantity)->toBe(10.0);
});
