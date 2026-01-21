<?php

declare(strict_types=1);

use Database\Factories\WorkflowFieldFactory;

test('to array', function (): void {
    $field = WorkflowFieldFactory::new()->create()->refresh();

    expect(array_keys($field->toArray()))->toBe([
        'id',
        'workflow_id',
        'name',
        'key',
        'type',
        'mastertable_id',
        'is_required',
        'placeholder',
        'default_value',
        'position',
        'is_active',
        'created_at',
        'updated_at',
    ]);
});
