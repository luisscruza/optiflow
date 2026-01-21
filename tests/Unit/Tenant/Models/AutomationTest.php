<?php

declare(strict_types=1);

use Database\Factories\AutomationFactory;

test('to array', function (): void {
    $automation = AutomationFactory::new()->create()->refresh();

    expect(array_keys($automation->toArray()))->toBe([
        'id',
        'workspace_id',
        'name',
        'is_active',
        'published_version',
        'created_at',
        'updated_at',
    ]);
});
