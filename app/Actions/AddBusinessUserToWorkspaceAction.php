<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionValidationException;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final readonly class AddBusinessUserToWorkspaceAction
{
    public function handle(User $user, Workspace $workspace, ?int $roleId): void
    {
        DB::transaction(function () use ($user, $workspace, $roleId): void {
            if ($workspace->hasUser($user)) {
                throw new ActionValidationException([
                    'workspace' => 'El usuario ya pertenece a este workspace.',
                ]);
            }

            $workspace->addUser($user);

            if ($roleId) {
                $role = Role::find($roleId);
                $roleWorkspaceId = $role?->getAttribute('workspace_id');

                if ($roleWorkspaceId !== null && (int) $roleWorkspaceId === $workspace->id) {
                    $user->assignRole($role);
                }
            }
        });
    }
}
