<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Tax;

test('to array', function (): void {
    $tax = Tax::factory()->create()->refresh();

    expect(array_keys($tax->toArray()))
        ->toBe([
            'id',
            'name',
            'rate',
            'is_default',
            'created_at',
            'updated_at',
            'rate_percentage',
        ]);
});

test('has many products', function (): void {
    $tax = Tax::factory()->create();
    Product::factory()->count(3)->create(['default_tax_id' => $tax->id]);

    expect($tax->products)->toHaveCount(3);
    expect($tax->products->first())->toBeInstanceOf(Product::class);
});

test('default scope filters correctly', function (): void {
    // Clear any existing taxes first
    Tax::query()->delete();

    Tax::factory()->create(['is_default' => true]);
    Tax::factory()->create(['is_default' => false]);
    Tax::factory()->create(['is_default' => false]);

    $defaultTaxes = Tax::default()->get();

    expect($defaultTaxes)->toHaveCount(1);
    expect($defaultTaxes->first()->is_default)->toBeTrue();
});

test('rate percentage accessor formats correctly', function (): void {
    $tax = Tax::factory()->create(['rate' => 18.00]);

    expect($tax->rate_percentage)->toBe('18.00%');
});

test('rate percentage accessor with different rates', function (float $rate, string $expected): void {
    $tax = Tax::factory()->withRate($rate)->create();

    expect($tax->rate_percentage)->toBe($expected);
})->with([
    [18.00, '18.00%'],
    [21.00, '21.00%'],
    [5.50, '5.50%'],
    [0.00, '0.00%'],
]);

test('casts rate to decimal', function (): void {
    $tax = Tax::factory()->create(['rate' => 18]);

    expect($tax->rate)->toBeString();
    expect($tax->rate)->toBe('18.00');
});

test('casts is_default to boolean', function (): void {
    $tax = Tax::factory()->create(['is_default' => 1]);

    expect($tax->is_default)->toBeBool();
    expect($tax->is_default)->toBeTrue();
});

test('default factory state creates default tax', function (): void {
    $tax = Tax::factory()->default()->create();

    expect($tax->is_default)->toBeTrue();
});

test('with rate factory state creates tax with specific rate', function (): void {
    $rate = 23.00;
    $tax = Tax::factory()->withRate($rate)->create();

    expect($tax->rate)->toBe('23.00');
    expect($tax->name)->toBe('IVA 23%');
});
