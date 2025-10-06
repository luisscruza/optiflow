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

test('belongs to invoice', function (): void {
    $invoice = Invoice::factory()->create();
    $invoiceItem = InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

    expect($invoiceItem->invoice)->toBeInstanceOf(Invoice::class);
    expect($invoiceItem->invoice->id)->toBe($invoice->id);
});

test('belongs to product', function (): void {
    $product = Product::factory()->create();
    $invoiceItem = InvoiceItem::factory()->create(['product_id' => $product->id]);

    expect($invoiceItem->product)->toBeInstanceOf(Product::class);
    expect($invoiceItem->product->id)->toBe($product->id);
});

test('belongs to tax', function (): void {
    $tax = Tax::factory()->create();
    $invoiceItem = InvoiceItem::factory()->create(['tax_id' => $tax->id]);

    expect($invoiceItem->tax)->toBeInstanceOf(Tax::class);
    expect($invoiceItem->tax->id)->toBe($tax->id);
});

test('for product scope filters correctly', function (): void {
    $product = Product::factory()->create();
    InvoiceItem::factory()->count(3)->create(['product_id' => $product->id]);
    InvoiceItem::factory()->count(2)->create();

    $items = InvoiceItem::forProduct($product)->get();

    expect($items)->toHaveCount(3);
    expect($items->first()->product_id)->toBe($product->id);
});

test('with discount scope filters correctly', function (): void {
    InvoiceItem::factory()->count(2)->create(['discount_amount' => 10]);
    InvoiceItem::factory()->count(3)->withoutDiscount()->create();

    $itemsWithDiscount = InvoiceItem::withDiscount()->get();

    expect($itemsWithDiscount)->toHaveCount(2);
    expect($itemsWithDiscount->first()->discount_amount)->toBeGreaterThan(0);
});

test('casts work correctly', function (): void {
    $invoiceItem = InvoiceItem::factory()->create();

    expect($invoiceItem->quantity)->toBeFloat();
    expect($invoiceItem->unit_price)->toBeFloat();
    expect($invoiceItem->total)->toBeFloat();
});

test('factory without discount state works', function (): void {
    $invoiceItem = InvoiceItem::factory()->withoutDiscount()->create();

    expect($invoiceItem->discount_amount)->toBe(0.0);
    expect($invoiceItem->discount_rate)->toBe(0.0);
});

test('factory with quantity state works', function (): void {
    $quantity = 25.0;
    $invoiceItem = InvoiceItem::factory()->withQuantity($quantity)->create();

    expect($invoiceItem->quantity)->toBe($quantity);
});

test('factory with unit price state works', function (): void {
    $unitPrice = 199.99;
    $invoiceItem = InvoiceItem::factory()->withUnitPrice($unitPrice)->create();

    expect($invoiceItem->unit_price)->toBe($unitPrice);
});
