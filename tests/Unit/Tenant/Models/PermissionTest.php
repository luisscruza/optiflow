<?php

declare(strict_types=1);

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
