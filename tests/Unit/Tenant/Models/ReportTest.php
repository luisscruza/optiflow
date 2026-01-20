<?php

declare(strict_types=1);

use App\Models\Report;

test('to array', function (): void {
    $report = Report::factory()->create()->refresh();

    expect(array_keys($report->toArray()))->toBe([
        'id',
        'type',
        'name',
        'description',
        'group',
        'config',
        'is_active',
        'created_at',
        'updated_at',
    ]);
});
