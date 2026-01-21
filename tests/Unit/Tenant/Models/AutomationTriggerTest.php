<?php

declare(strict_types=1);

use Database\Factories\AutomationTriggerFactory;

test('to array', function (): void {
    $trigger = AutomationTriggerFactory::new()->create()->refresh();

    expect(array_keys($trigger->toArray()))->toBe([
        'id',
        'automation_id',
        'workspace_id',
        'event_key',
        'workflow_id',
        'workflow_stage_id',
        'is_active',
        'created_at',
        'updated_at',
    ]);
});
