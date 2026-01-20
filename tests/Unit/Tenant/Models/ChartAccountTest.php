<?php

declare(strict_types=1);

use App\Models\ChartAccount;

test('to array', function (): void {
    $chartAccount = ChartAccount::factory()->create()->refresh();

    expect(array_keys($chartAccount->toArray()))->toBe([
        'id',
        'code',
        'name',
        'type',
        'parent_id',
        'description',
        'is_active',
        'is_system',
        'created_at',
        'updated_at',
    ]);
});
