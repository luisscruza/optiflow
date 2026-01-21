<?php

declare(strict_types=1);

use Database\Factories\AutomationRunFactory;

test('to array', function (): void {
    $run = AutomationRunFactory::new()->create()->refresh();

    expect(array_keys($run->toArray()))->toBe([
        'id',
        'automation_id',
        'automation_version_id',
        'workspace_id',
        'trigger_event_key',
        'subject_type',
        'subject_id',
        'status',
        'pending_nodes',
        'started_at',
        'finished_at',
        'error',
        'created_at',
        'updated_at',
    ]);
});
