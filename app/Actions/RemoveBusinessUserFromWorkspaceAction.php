<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionValidationException;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

final readonly class RemoveBusinessUserFromWorkspaceAction
{
    public function handle(User $user, Workspace $workspace): void
    {
        DB::transaction(function () use ($user, $workspace): void {
            if (! $workspace->hasUser($user)) {
                throw new ActionValidationException([
                    'workspace' => 'El usuario no pertenece a este workspace.',
                ]);
            }

            $workspaceRoles = $user->roles()
                ->where('roles.workspace_id', $workspace->id)
                ->get();

            foreach ($workspaceRoles as $role) {
                $user->removeRole($role);
            }

            $workspace->removeUser($user);
        });
    }
}
