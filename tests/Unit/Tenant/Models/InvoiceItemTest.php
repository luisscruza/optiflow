<?php

declare(strict_types=1);

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Tax;

test('to array', function (): void {
    $invoiceItem = InvoiceItem::factory()->create()->refresh();

    expect(array_keys($invoiceItem->toArray()))
        ->toBe([
            'id',
            'invoice_id',
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
            'product',
        ]);
});