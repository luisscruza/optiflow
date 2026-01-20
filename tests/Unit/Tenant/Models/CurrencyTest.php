<?php

declare(strict_types=1);

use App\Models\Currency;

test('to array', function (): void {
    $currency = Currency::factory()->create()->refresh();

    expect(array_keys($currency->toArray()))->toBe([
        'id',
        'name',
        'code',
        'symbol',
        'is_default',
        'is_active',
        'created_at',
        'updated_at',
    ]);
});
