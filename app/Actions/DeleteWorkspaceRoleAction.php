<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionNotFoundException;
use App\Exceptions\ActionValidationException;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final readonly class DeleteWorkspaceRoleAction
{
    public function handle(Workspace $workspace, Role $role): void
    {
        DB::transaction(function () use ($workspace, $role): void {
            if ($role->getAttribute('workspace_id') !== $workspace->id) {
                throw new ActionNotFoundException('Role does not belong to workspace.');
            }

            if ($role->users()->count() > 0) {
                throw new ActionValidationException([
                    'role' => 'No se puede eliminar un rol que está asignado a usuarios.',
                ]);
            }

            $role->delete();
        });
    }
}
