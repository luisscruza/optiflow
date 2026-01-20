<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionNotFoundException;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final readonly class UpdateWorkspaceRoleAction
{
    /**
     * @param  array{name: string, permissions: array<int, string>}  $data
     */
    public function handle(Workspace $workspace, Role $role, array $data): Role
    {
        return DB::transaction(function () use ($workspace, $role, $data): Role {
            if ($role->getAttribute('workspace_id') !== $workspace->id) {
                throw new ActionNotFoundException('Role does not belong to workspace.');
            }

            $role->update([
                'name' => $data['name'],
            ]);

            $role->syncPermissions($data['permissions']);

            return $role;
        });
    }
}
