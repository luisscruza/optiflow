<?php

declare(strict_types=1);

use App\Models\ProductStock;

test('to array', function (): void {
    $productStock = ProductStock::factory()->create()->refresh();

    expect(array_keys($productStock->toArray()))->toBe([
        'id',
        'product_id',
        'workspace_id',
        'supplier_id',
        'quantity',
        'minimum_quantity',
        'created_at',
        'updated_at',
    ]);
});
