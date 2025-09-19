<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\UserInvitation;
use Exception;

final class AcceptInvitationAction
{
    /**
     * Accept a workspace invitation.
     *
     * @throws Exception
     */
    public function handle(UserInvitation $invitation, User $user): void
    {
        if (! $invitation->isPending()) {
            throw new Exception('This invitation is no longer valid.');
        }

        if ($invitation->isExpired()) {
            throw new Exception('This invitation has expired.');
        }

        $invitation->accept($user);

        $invitation->workspace->users()->syncWithoutDetaching([
            $user->id => [
                'role' => $invitation->role->value,
                'joined_at' => now(),
            ],
        ]);

        if (! $user->current_workspace_id) {
            $user->switchToWorkspace($invitation->workspace);
        }
    }
}
