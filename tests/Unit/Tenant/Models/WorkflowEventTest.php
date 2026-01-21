<?php

declare(strict_types=1);

use Database\Factories\WorkflowEventFactory;

test('to array', function (): void {
    $event = WorkflowEventFactory::new()->create()->refresh();

    expect(array_keys($event->toArray()))->toBe([
        'id',
        'workflow_job_id',
        'from_stage_id',
        'to_stage_id',
        'event_type',
        'user_id',
        'metadata',
        'created_at',
        'updated_at',
        'event_type_label',
    ]);
});
