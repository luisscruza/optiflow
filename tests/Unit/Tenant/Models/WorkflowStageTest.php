<?php

declare(strict_types=1);

use Database\Factories\WorkflowStageFactory;

test('to array', function (): void {
    $stage = WorkflowStageFactory::new()->create()->refresh();

    expect(array_keys($stage->toArray()))->toBe([
        'id',
        'workflow_id',
        'name',
        'description',
        'color',
        'position',
        'is_active',
        'is_initial',
        'is_final',
        'created_at',
        'updated_at',
    ]);
});
