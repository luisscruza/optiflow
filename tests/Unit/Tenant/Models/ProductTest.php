<?php

declare(strict_types=1);

use App\Models\Product;

test('to array', function (): void {
    $product = Product::factory()->create()->refresh();

    expect(array_keys($product->toArray()))->toBe([
        'id',
        'name',
        'sku',
        'description',
        'price',
        'cost',
        'track_stock',
        'allow_negative_stock',
        'default_tax_id',
        'created_at',
        'updated_at',
        'unit',
        'product_category_id',
    ]);
});
