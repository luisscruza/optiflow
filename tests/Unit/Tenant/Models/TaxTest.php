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
            'type',
            'rate',
            'is_default',
            'created_at',
            'updated_at',
            'rate_percentage',
        ]);
});