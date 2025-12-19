<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Context;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

final class WorkspaceRoleController extends Controller
{
    /**
     * Display a listing of roles for the current workspace.
     */
    public function index(#[CurrentUser] User $user): Response
    {
        $workspace = Context::get('workspace');

        $roles = Role::query()
            ->where('workspace_id', $workspace->id)
            ->with('permissions')
            ->get()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->map(fn (Permission $permission): array => [
                    'name' => $permission->name,
                    'label' => $permission->getLabel(),
                ])->toArray(),
                'users_count' => $role->users()->count(),
            ]);

        $permissions = Permission::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Permission $permission): array => [
                'name' => $permission->name,
                'label' => $permission->getLabel(),
                'group' => $permission->getGroup(),
            ])
            ->groupBy('group');

        return Inertia::render('workspace/roles', [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'is_owner' => $workspace->owner_id === $user->id,
            ],
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $workspace = Context::get('workspace');

        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
            'workspace_id' => $workspace->id,
        ]);

        $role->syncPermissions($request->validated('permissions'));

        return redirect()->back()->with('success', 'Rol creado exitosamente.');
    }

    /**
     * Update the specified role in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $workspace = Context::get('workspace');

        if ($role->workspace_id !== $workspace->id) {
            abort(404);
        }

        $role->update([
            'name' => $request->validated('name'),
        ]);

        $role->syncPermissions($request->validated('permissions'));

        return redirect()->back()->with('success', 'Rol actualizado exitosamente.');
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role): RedirectResponse
    {
        $workspace = Context::get('workspace');

        if ($role->workspace_id !== $workspace->id) {
            abort(404);
        }

        // Check if the role is being used by any users
        if ($role->users()->count() > 0) {
            return redirect()->back()->withErrors([
                'role' => 'No se puede eliminar un rol que está asignado a usuarios.',
            ]);
        }

        $role->delete();

        return redirect()->back()->with('success', 'Rol eliminado exitosamente.');
    }
}
