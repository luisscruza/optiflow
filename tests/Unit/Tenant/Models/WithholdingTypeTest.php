<?php

declare(strict_types=1);

use App\Models\WithholdingType;

test('to array', function (): void {
    $withholdingType = WithholdingType::factory()->create()->refresh();

    expect(array_keys($withholdingType->toArray()))->toBe([
        'id',
        'name',
        'code',
        'percentage',
        'chart_account_id',
        'description',
        'is_active',
        'created_at',
        'updated_at',
    ]);
});
