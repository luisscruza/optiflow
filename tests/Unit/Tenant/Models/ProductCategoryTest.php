<?php

declare(strict_types=1);

use Database\Factories\ProductCategoryFactory;

test('to array', function (): void {
    $category = ProductCategoryFactory::new()->create()->refresh();

    expect(array_keys($category->toArray()))->toBe([
        'id',
        'name',
        'slug',
        'description',
        'created_at',
        'updated_at',
    ]);
});
