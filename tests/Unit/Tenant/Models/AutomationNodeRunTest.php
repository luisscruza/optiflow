<?php

declare(strict_types=1);

use Database\Factories\AutomationNodeRunFactory;

test('to array', function (): void {
    $nodeRun = AutomationNodeRunFactory::new()->create()->refresh();

    expect(array_keys($nodeRun->toArray()))->toBe([
        'id',
        'automation_run_id',
        'node_id',
        'node_type',
        'status',
        'attempts',
        'input',
        'output',
        'error',
        'started_at',
        'finished_at',
        'created_at',
        'updated_at',
    ]);
});
