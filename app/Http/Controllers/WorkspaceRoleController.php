<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateWorkspaceRoleAction;
use App\Actions\DeleteWorkspaceRoleAction;
use App\Actions\UpdateWorkspaceRoleAction;
use App\Exceptions\ActionNotFoundException;
use App\Exceptions\ActionValidationException;
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

final class WorkspaceRoleController
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
    public function store(StoreRoleRequest $request, CreateWorkspaceRoleAction $action): RedirectResponse
    {
        $workspace = Context::get('workspace');

        $action->handle($workspace, $request->validated());

        return redirect()->back()->with('success', 'Rol creado exitosamente.');
    }

    /**
     * Update the specified role in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role, UpdateWorkspaceRoleAction $action): RedirectResponse
    {
        $workspace = Context::get('workspace');

        try {
            $action->handle($workspace, $role, $request->validated());
        } catch (ActionNotFoundException) {
            abort(404);
        }

        return redirect()->back()->with('success', 'Rol actualizado exitosamente.');
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role, DeleteWorkspaceRoleAction $action): RedirectResponse
    {
        $workspace = Context::get('workspace');

        try {
            $action->handle($workspace, $role);
        } catch (ActionNotFoundException) {
            abort(404);
        } catch (ActionValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors());
        }

        return redirect()->back()->with('success', 'Rol eliminado exitosamente.');
    }
}
