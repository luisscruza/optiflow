<?php

declare(strict_types=1);

use Database\Factories\WorkflowFactory;

test('to array', function (): void {
    $workflow = WorkflowFactory::new()->create()->refresh();

    expect(array_keys($workflow->toArray()))->toBe([
        'id',
        'name',
        'is_active',
        'invoice_requirement',
        'prescription_requirement',
        'created_at',
        'updated_at',
    ]);
});
