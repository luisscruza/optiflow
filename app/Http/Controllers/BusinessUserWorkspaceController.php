<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\AddUserToWorkspaceRequest;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;

final class BusinessUserWorkspaceController extends Controller
{
    /**
     * Add user to a workspace.
     */
    public function store(
        AddUserToWorkspaceRequest $request,
        User $user,
        #[CurrentUser] User $currentUser
    ): RedirectResponse {
        // Check if user is business owner
        if (in_array($currentUser->business_role, [UserRole::Owner, UserRole::Admin])) {
            abort(403, 'Solo el propietario del negocio puede agregar usuarios a workspaces.');
        }

        $workspace = Workspace::findOrFail($request->validated('workspace_id'));

        if ($workspace->hasUser($user)) {
            return redirect()->back()->withErrors([
                'workspace' => 'El usuario ya pertenece a este workspace.',
            ]);
        }

        $workspace->addUser($user);

        // Assign role if provided
        if ($request->validated('role_id')) {
            $role = Role::find($request->validated('role_id'));
            if ($role && $role->workspace_id === $workspace->id) {
                $user->assignRole($role);
            }
        }

        return redirect()->back()->with('success', 'Usuario agregado al workspace exitosamente.');
    }

    /**
     * Remove user from a workspace.
     */
    public function destroy(User $user, Workspace $workspace, #[CurrentUser] User $currentUser): RedirectResponse
    {
        // Check if user is business owner
        if (in_array($currentUser->business_role, [UserRole::Owner, UserRole::Admin])) {
            abort(403, 'Solo el propietario del negocio puede remover usuarios de workspaces.');
        }

        if (! $workspace->hasUser($user)) {
            return redirect()->back()->withErrors([
                'workspace' => 'El usuario no pertenece a este workspace.',
            ]);
        }

        $workspaceRoles = $user->roles()
            ->where('roles.workspace_id', $workspace->id)
            ->get();

        foreach ($workspaceRoles as $role) {
            $user->removeRole($role);
        }

        $workspace->removeUser($user);

        return redirect()->back()->with('success', 'Usuario removido del workspace exitosamente.');
    }
}
