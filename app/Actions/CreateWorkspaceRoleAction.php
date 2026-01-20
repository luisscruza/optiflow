<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final readonly class CreateWorkspaceRoleAction
{
    /**
     * @param  array{name: string, permissions: array<int, string>}  $data
     */
    public function handle(Workspace $workspace, array $data): Role
    {
        return DB::transaction(function () use ($workspace, $data): Role {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'web',
                'workspace_id' => $workspace->id,
            ]);

            $role->syncPermissions($data['permissions']);

            return $role;
        });
    }
}
