<?php

declare(strict_types=1);

use Database\Factories\MastertableFactory;

test('to array', function (): void {
    $mastertable = MastertableFactory::new()->create()->refresh();

    expect(array_keys($mastertable->toArray()))->toBe([
        'id',
        'name',
        'alias',
        'description',
        'created_at',
        'updated_at',
    ]);
});
