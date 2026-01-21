<?php

declare(strict_types=1);

use Database\Factories\UserInvitationFactory;

test('to array', function (): void {
    $invitation = UserInvitationFactory::new()->create()->refresh();

    expect(array_keys($invitation->toArray()))->toBe([
        'id',
        'email',
        'token',
        'workspace_id',
        'invited_by',
        'role',
        'status',
        'expires_at',
        'accepted_at',
        'user_id',
        'created_at',
        'updated_at',
    ]);
});
