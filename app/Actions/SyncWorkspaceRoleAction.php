<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final readonly class SyncWorkspaceRoleAction
{
    /**
     * Execute the action.
     * Sync all existing global roles to a new workspace.
     */
    public function handle(Workspace $workspace): void
    {
        DB::transaction(function () use ($workspace): void {
            // Get all unique role names from existing roles
            $roleTemplates = Role::query()
                ->with('permissions')
                ->get()
                ->groupBy('name')
                ->map(fn ($roleGroup) => $roleGroup->first());

            foreach ($roleTemplates as $templateRole) {
                // Check if role already exists in this workspace
                $existingRole = Role::query()
                    ->where('name', $templateRole->name)
                    ->where('workspace_id', $workspace->id)
                    ->first();

                if (! $existingRole) {
                    // Create the role in this workspace
                    $role = Role::create([
                        'name' => $templateRole->name,
                        'guard_name' => 'web',
                        'workspace_id' => $workspace->id,
                    ]);

                    // Sync permissions from the template role
                    $permissionNames = $templateRole->permissions->pluck('name')->toArray();
                    $role->syncPermissions($permissionNames);
                }
            }
        });
    }
}
