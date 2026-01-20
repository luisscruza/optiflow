<?php

declare(strict_types=1);

use App\Models\User;

test('to array', function (): void {
    $user = User::factory()->create()->refresh();

    expect(array_keys($user->toArray()))->toBe([
        'id',
        'name',
        'email',
        'email_verified_at',
        'business_role',
        'created_at',
        'updated_at',
        'current_workspace_id',
        'dashboard_layout',
        'password_changed_at',
        'last_activity_at',
    ]);
});
