<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final class SyncGlobalRoleController extends Controller
{
    /**
     * Sync a role to all workspaces that don't have it yet.
     */
    public function __invoke(string $roleName): RedirectResponse
    {
        $originalName = urldecode($roleName);

        // Get the template role (first one found)
        $templateRole = Role::query()
            ->where('name', $originalName)
            ->with('permissions')
            ->first();

        if (! $templateRole) {
            return redirect()->back()->withErrors([
                'role' => 'Rol no encontrado.',
            ]);
        }

        $workspaces = Workspace::all();
        $permissionNames = $templateRole->permissions->pluck('name')->toArray();

        DB::transaction(function () use ($workspaces, $originalName, $permissionNames): void {
            foreach ($workspaces as $workspace) {
                $existingRole = Role::query()
                    ->where('name', $originalName)
                    ->where('workspace_id', $workspace->id)
                    ->first();

                if (! $existingRole) {
                    $role = Role::create([
                        'name' => $originalName,
                        'guard_name' => 'web',
                        'workspace_id' => $workspace->id,
                    ]);
                    $role->syncPermissions($permissionNames);
                }
            }
        });

        return redirect()->back()->with('success', 'Rol sincronizado en todos los workspaces.');
    }
}
