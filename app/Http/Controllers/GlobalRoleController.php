<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateGlobalRoleAction;
use App\Actions\DeleteGlobalRoleAction;
use App\Actions\UpdateGlobalRoleAction;
use App\Exceptions\ActionValidationException;
use App\Http\Requests\StoreGlobalRoleRequest;
use App\Http\Requests\UpdateGlobalRoleRequest;
use App\Models\Permission;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
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
    public function store(StoreGlobalRoleRequest $request, CreateGlobalRoleAction $action): RedirectResponse
    {
        $workspaces = Workspace::all();

        $action->handle($request->validated(), $workspaces);

        return redirect()->back()->with('success', 'Rol creado exitosamente en todos los workspaces.');
    }

    /**
     * Update the specified role across all workspaces.
     */
    public function update(UpdateGlobalRoleRequest $request, string $roleName, UpdateGlobalRoleAction $action): RedirectResponse
    {
        $workspaces = Workspace::all();
        $originalName = urldecode($roleName);

        $action->handle($request->validated(), $workspaces, $originalName);

        return redirect()->back()->with('success', 'Rol actualizado exitosamente en todos los workspaces.');
    }

    /**
     * Remove the specified role from all workspaces.
     */
    public function destroy(string $roleName, DeleteGlobalRoleAction $action): RedirectResponse
    {
        $originalName = urldecode($roleName);

        try {
            $action->handle($originalName);
        } catch (ActionValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors());
        }

        return redirect()->back()->with('success', 'Rol eliminado exitosamente de todos los workspaces.');
    }
}
