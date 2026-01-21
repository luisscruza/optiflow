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

test('handles stock checks and adjustments', function (): void {
    $stock = ProductStock::factory()->create([
        'quantity' => 5,
        'minimum_quantity' => 10,
    ]);

    expect($stock->isLow())->toBeTrue()
        ->and($stock->isSufficient(3))->toBeTrue()
        ->and($stock->isSufficient(15))->toBeFalse()
        ->and($stock->status)->toBe('low_stock')
        ->and($stock->level_percentage)->toBe(50.0);

    $stock->incrementStock(10);
    $stock->refresh();
    expect($stock->quantity)->toBe('15.00');

    expect($stock->decrementStock(5))->toBeTrue();
    $stock->refresh();
    expect($stock->quantity)->toBe('10.00');

    expect($stock->decrementStock(20))->toBeFalse();
});
