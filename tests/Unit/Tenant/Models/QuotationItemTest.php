<?php

declare(strict_types=1);

use App\Models\QuotationItem;

test('to array', function (): void {
    $item = QuotationItem::factory()->create()->refresh();

    expect(array_keys($item->toArray()))->toBe([
        'id',
        'quotation_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'subtotal',
        'discount_amount',
        'tax_id',
        'tax_rate',
        'total',
        'created_at',
        'updated_at',
        'tax_amount',
        'discount_rate',
    ]);
});
