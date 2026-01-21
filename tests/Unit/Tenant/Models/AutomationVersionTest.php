<?php

declare(strict_types=1);

use Database\Factories\AutomationVersionFactory;

test('to array', function (): void {
    $version = AutomationVersionFactory::new()->create()->refresh();

    expect(array_keys($version->toArray()))->toBe([
        'id',
        'automation_id',
        'version',
        'definition',
        'created_by',
        'created_at',
        'updated_at',
    ]);
});
