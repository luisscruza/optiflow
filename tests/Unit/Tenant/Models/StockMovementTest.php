<?php

declare(strict_types=1);

use App\Models\StockMovement;

test('to array', function (): void {
    $stockMovement = StockMovement::factory()->create()->refresh();

    expect(array_keys($stockMovement->toArray()))->toBe([
        'id',
        'workspace_id',
        'product_id',
        'type',
        'quantity',
        'unit_cost',
        'total_cost',
        'related_invoice_id',
        'user_id',
        'note',
        'from_workspace_id',
        'to_workspace_id',
        'reference_number',
        'created_at',
        'updated_at',
    ]);
});
