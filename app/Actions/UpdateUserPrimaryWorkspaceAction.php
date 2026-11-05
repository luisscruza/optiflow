<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionValidationException;
use App\Models\User;
use App\Models\Workspace;

final readonly class UpdateUserPrimaryWorkspaceAction
{
    public function handle(User $user, ?Workspace $workspace): void
    {
        if ($workspace && ! $workspace->hasUser($user)) {
            throw new ActionValidationException([
                'workspace_id' => 'El usuario no pertenece a esta sucursal.',
            ]);
        }

        $user->update(['primary_workspace_id' => $workspace?->id]);
    }
}
