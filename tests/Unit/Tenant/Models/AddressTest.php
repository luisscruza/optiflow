<?php

declare(strict_types=1);

use App\Models\Address;

test('to array', function (): void {
    $address = Address::factory()->create()->refresh();

    expect(array_keys($address->toArray()))->toBe([
        'id',
        'contact_id',
        'type',
        'province',
        'municipality',
        'country',
        'description',
        'is_primary',
        'created_at',
        'updated_at',
        'full_address',
    ]);
});
