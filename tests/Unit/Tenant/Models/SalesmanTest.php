<?php

declare(strict_types=1);

use App\Models\Salesman;

test('to array', function (): void {
    $salesman = Salesman::factory()->create()->refresh();

    expect(array_keys($salesman->toArray()))->toBe([
        'id',
        'name',
        'surname',
        'user_id',
        'created_at',
        'updated_at',
        'full_name',
    ]);
});
