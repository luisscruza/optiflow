<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionValidationException;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final readonly class UpdateBusinessUserWorkspaceRolesAction
{
    /**
     * @param  array<int, int>  $roleIds
     */
    public function handle(User $user, Workspace $workspace, array $roleIds): void
    {
        DB::transaction(function () use ($user, $workspace, $roleIds): void {
            if (! $workspace->hasUser($user)) {
                throw new ActionValidationException([
                    'workspace' => 'El usuario no pertenece a este workspace.',
                ]);
            }

            app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);

            $user->roles()
                ->where('roles.workspace_id', $workspace->id)
                ->each(function (Role $role) use ($user): void {
                    $user->removeRole($role);
                });

            foreach ($roleIds as $roleId) {
                $role = Role::find($roleId);
                $roleWorkspaceId = $role?->getAttribute('workspace_id');

                if ($roleWorkspaceId !== null && (int) $roleWorkspaceId === $workspace->id) {
                    $user->assignRole($role);
                }
            }
        });
    }
}
