<?php

declare(strict_types=1);

use App\Enums\Permission as PermissionEnum;
use Database\Factories\PermissionFactory;

test('to array', function (): void {
    $permission = PermissionFactory::new()->create()->refresh();

    expect(array_keys($permission->toArray()))->toBe([
        'id',
        'name',
        'guard_name',
        'created_at',
        'updated_at',
    ]);
});

test('derives label and group', function (): void {
    $permission = PermissionFactory::new()->make([
        'name' => 'custom.permission',
    ]);

    expect($permission->getLabel())->toBe('Custom permission')
        ->and($permission->getGroup())->toBe('Custom');
});

test('derives label and group for history logs permission', function (): void {
    $permission = PermissionFactory::new()->make([
        'name' => PermissionEnum::ViewHistoryLogs->value,
    ]);

    expect($permission->getLabel())->toBe('Ver historial de cambios')
        ->and($permission->getGroup())->toBe('Facturas');
});
