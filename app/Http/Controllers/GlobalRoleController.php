<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreGlobalRoleRequest;
use App\Http\Requests\UpdateGlobalRoleRequest;
use App\Models\Permission;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

final class GlobalRoleController extends Controller
{
    /**
     * Display a listing of global roles (grouped by name across workspaces).
     */
    public function index(): Response
    {
        $workspaces = Workspace::query()->orderBy('name')->get();

        // Get all roles grouped by name
        $rolesGrouped = Role::query()
            ->with('permissions')
            ->orderBy('name')
            ->get()
            ->groupBy('name');

        $roles = $rolesGrouped->map(function ($roleGroup, $name) use ($workspaces): array {
            $firstRole = $roleGroup->first();
            $workspaceIds = $roleGroup->pluck('workspace_id')->toArray();

            $totalUsersCount = $roleGroup->sum(fn (Role $role): int => $role->users()->count());

            return [
                'name' => $name,
                'permissions' => $firstRole->permissions->map(fn (Permission $permission): array => [
                    'name' => $permission->name,
                    'label' => $permission->getLabel(),
                ])->toArray(),
                'users_count' => $totalUsersCount,
                'workspace_ids' => $workspaceIds,
                'workspaces_count' => count($workspaceIds),
                'total_workspaces' => $workspaces->count(),
                'is_synced' => count($workspaceIds) === $workspaces->count(),
            ];
        })->values();

        $permissions = Permission::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Permission $permission): array => [
                'name' => $permission->name,
                'label' => $permission->getLabel(),
                'group' => $permission->getGroup(),
            ])
            ->groupBy('group');

        return Inertia::render('business/roles', [
            'roles' => $roles,
            'permissions' => $permissions,
            'workspaces' => $workspaces->map(fn (Workspace $workspace): array => [
                'id' => $workspace->id,
                'name' => $workspace->name,
            ]),
        ]);
    }

    /**
     * Store a newly created role across all workspaces.
     */
    public function store(StoreGlobalRoleRequest $request): RedirectResponse
    {
        $workspaces = Workspace::all();

        DB::transaction(function () use ($request, $workspaces): void {
            foreach ($workspaces as $workspace) {
                $role = Role::create([
                    'name' => $request->validated('name'),
                    'guard_name' => 'web',
                    'workspace_id' => $workspace->id,
                ]);

                $role->syncPermissions($request->validated('permissions'));
            }
        });

        return redirect()->back()->with('success', 'Rol creado exitosamente en todos los workspaces.');
    }

    /**
     * Update the specified role across all workspaces.
     */
    public function update(UpdateGlobalRoleRequest $request, string $roleName): RedirectResponse
    {
        $workspaces = Workspace::all();
        $originalName = urldecode($roleName);

        DB::transaction(function () use ($request, $workspaces, $originalName): void {
            foreach ($workspaces as $workspace) {
                $role = Role::query()
                    ->where('name', $originalName)
                    ->where('workspace_id', $workspace->id)
                    ->first();

                if ($role) {
                    $role->update([
                        'name' => $request->validated('name'),
                    ]);
                    $role->syncPermissions($request->validated('permissions'));
                } else {
                    // Create the role in this workspace if it doesn't exist (sync)
                    $role = Role::create([
                        'name' => $request->validated('name'),
                        'guard_name' => 'web',
                        'workspace_id' => $workspace->id,
                    ]);
                    $role->syncPermissions($request->validated('permissions'));
                }
            }
        });

        return redirect()->back()->with('success', 'Rol actualizado exitosamente en todos los workspaces.');
    }

    /**
     * Remove the specified role from all workspaces.
     */
    public function destroy(string $roleName): RedirectResponse
    {
        $originalName = urldecode($roleName);

        // Check if any role with this name has users assigned
        $rolesWithUsers = Role::query()
            ->where('name', $originalName)
            ->get()
            ->filter(fn (Role $role): bool => $role->users()->count() > 0);

        if ($rolesWithUsers->isNotEmpty()) {
            return redirect()->back()->withErrors([
                'role' => 'No se puede eliminar un rol que está asignado a usuarios en algún workspace.',
            ]);
        }

        DB::transaction(function () use ($originalName): void {
            Role::query()
                ->where('name', $originalName)
                ->delete();
        });

        return redirect()->back()->with('success', 'Rol eliminado exitosamente de todos los workspaces.');
    }

    /**
     * Sync a role to all workspaces that don't have it yet.
     */
    public function sync(string $roleName): RedirectResponse
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
