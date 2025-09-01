<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\Workspace;

final class DeleteWorkspaceAction
{
    public function handle(User $user, Workspace $workspace): void
    {
        // Only owner can delete workspace
        if ($workspace->owner_id !== $user->id) {
            abort(403, 'Only the workspace owner can delete it.');
        }

        $workspace->delete();
    }
}
