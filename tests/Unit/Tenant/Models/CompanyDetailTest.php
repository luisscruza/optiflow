<?php

declare(strict_types=1);

use Database\Factories\CompanyDetailFactory;

test('to array', function (): void {
    $detail = CompanyDetailFactory::new()->create()->refresh();

    expect(array_keys($detail->toArray()))->toBe([
        'id',
        'key',
        'value',
        'created_at',
        'updated_at',
    ]);
});
