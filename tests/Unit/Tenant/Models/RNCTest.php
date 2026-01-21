<?php

declare(strict_types=1);

use App\Models\RNC;

test('to array', function (): void {
    $rnc = new RNC([
        'identification' => '123456789',
        'name' => 'Opticanet',
        'comercial_name' => 'Opticanet',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(array_keys($rnc->toArray()))->toBe([
        'identification',
        'name',
        'comercial_name',
        'status',
        'created_at',
        'updated_at',
    ]);
});
