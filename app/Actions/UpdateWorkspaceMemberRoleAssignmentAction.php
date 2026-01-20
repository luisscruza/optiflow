<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionNotFoundException;
use App\Exceptions\ActionValidationException;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final readonly class UpdateWorkspaceMemberRoleAssignmentAction
{
    public function handle(Workspace $workspace, User $member, int $roleId): void
    {
        DB::transaction(function () use ($workspace, $member, $roleId): void {
            if (! $workspace->hasUser($member)) {
                throw new ActionNotFoundException('Member not found in workspace.');
            }

            $role = Role::query()
                ->where('id', $roleId)
                ->where('workspace_id', $workspace->id)
                ->first();

            if (! $role) {
                throw new ActionValidationException([
                    'role' => 'El rol seleccionado no es válido.',
                ]);
            }

            $existingRoles = $member->roles()
                ->where('roles.workspace_id', $workspace->id)
                ->get();

            foreach ($existingRoles as $existingRole) {
                $member->removeRole($existingRole);
            }

            $member->assignRole($role);
        });
    }
}
