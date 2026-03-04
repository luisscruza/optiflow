<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Workspace;

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
        'is_active',
        'allow_negative_stock',
        'default_tax_id',
        'created_at',
        'updated_at',
        'unit',
        'product_category_id',
    ]);
});

test('is active by default', function (): void {
    $product = Product::factory()->create();

    expect($product->is_active)->toBeTrue();
});

test('inactive products are excluded from default queries', function (): void {
    Product::factory()->create(['is_active' => true]);
    $inactive = Product::factory()->create(['is_active' => false]);

    $ids = Product::query()->pluck('id')->toArray();

    expect($ids)->not->toContain($inactive->id);
});

test('withInactive scope includes inactive products', function (): void {
    $active = Product::factory()->create(['is_active' => true]);
    $inactive = Product::factory()->create(['is_active' => false]);

    $ids = Product::withInactive()->pluck('id')->toArray();

    expect($ids)->toContain($active->id)
        ->and($ids)->toContain($inactive->id);
});

test('calculates stock and profitability', function (): void {
    $product = Product::factory()->create([
        'price' => 100,
        'cost' => 40,
        'track_stock' => true,
    ]);

    $workspace = Workspace::factory()->create();

    ProductStock::factory()->create([
        'product_id' => $product->id,
        'workspace_id' => $workspace->id,
        'quantity' => 5,
        'minimum_quantity' => 1,
    ]);

    expect($product->profit)->toBe(60.0)
        ->and($product->profit_margin)->toBe(150.0)
        ->and($product->getStockQuantityForWorkspace($workspace))->toBe(5.0)
        ->and($product->hasSufficientStock($workspace, 2))->toBeTrue()
        ->and($product->hasSufficientStock($workspace, 10))->toBeFalse();
});
