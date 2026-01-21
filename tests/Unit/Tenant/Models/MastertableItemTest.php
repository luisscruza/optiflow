<?php

declare(strict_types=1);

use Database\Factories\MastertableItemFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

test('to array', function (): void {
    $item = MastertableItemFactory::new()->create()->refresh();

    expect(array_keys($item->toArray()))->toBe([
        'id',
        'mastertable_id',
        'name',
        'created_at',
        'updated_at',
    ]);
});

test('defines mastertable relation', function (): void {
    $item = MastertableItemFactory::new()->make();

    expect($item->mastertable())->toBeInstanceOf(BelongsTo::class);
});
