<?php

declare(strict_types=1);

use Database\Factories\WorkflowJobFactory;

test('to array', function (): void {
    $job = WorkflowJobFactory::new()->create()->refresh();

    expect(array_keys($job->toArray()))->toBe([
        'id',
        'workspace_id',
        'workflow_id',
        'workflow_stage_id',
        'contact_id',
        'invoice_id',
        'prescription_id',
        'notes',
        'metadata',
        'priority',
        'due_date',
        'started_at',
        'completed_at',
        'canceled_at',
        'created_at',
        'updated_at',
    ]);
});
