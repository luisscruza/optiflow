<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Workspace;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final readonly class CreateGlobalRoleAction
{
    /**
     * @param  array{name: string, permissions: array<int, string>}  $data
     * @param  Collection<int, Workspace>  $workspaces
     */
    public function handle(array $data, Collection $workspaces): void
    {
        DB::transaction(function () use ($data, $workspaces): void {
            foreach ($workspaces as $workspace) {
                $role = Role::create([
                    'name' => $data['name'],
                    'guard_name' => 'web',
                    'workspace_id' => $workspace->id,
                ]);

                $role->syncPermissions($data['permissions']);
            }
        });
    }
}
