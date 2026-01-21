<?php

declare(strict_types=1);

use App\Enums\ReportGroup;
use App\Enums\ReportType;
use App\Models\Report;

test('to array', function (): void {
    $report = new Report([
        'id' => 1,
        'type' => ReportType::GeneralSales,
        'name' => 'General Sales',
        'description' => null,
        'group' => ReportGroup::Sales,
        'config' => null,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

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
