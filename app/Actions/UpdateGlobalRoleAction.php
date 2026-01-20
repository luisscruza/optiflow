<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Workspace;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final readonly class UpdateGlobalRoleAction
{
    /**
     * @param  array{name: string, permissions: array<int, string>}  $data
     * @param  Collection<int, Workspace>  $workspaces
     */
    public function handle(array $data, Collection $workspaces, string $originalName): void
    {
        DB::transaction(function () use ($data, $workspaces, $originalName): void {
            foreach ($workspaces as $workspace) {
                $role = Role::query()
                    ->where('name', $originalName)
                    ->where('workspace_id', $workspace->id)
                    ->first();

                if ($role) {
                    $role->update([
                        'name' => $data['name'],
                    ]);

                    $role->syncPermissions($data['permissions']);

                    continue;
                }

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
