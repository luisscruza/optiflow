<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AssignRoleAction;
use App\Enums\UserRole;
use App\Http\Requests\UpdateUserWorkspaceRolesRequest;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;

final class BusinessUserWorkspaceRoleController extends Controller
{
    /**
     * Update user roles in a specific workspace.
     */
    public function update(
        UpdateUserWorkspaceRolesRequest $request,
        int $userId,
        int $workspaceId,
        #[CurrentUser] User $currentUser,
    ): RedirectResponse {
        $user = User::findOrFail($userId);
        $workspace = Workspace::findOrFail($workspaceId);

        if ($currentUser->business_role !== UserRole::Owner) {
            abort(403, 'Solo el propietario del negocio puede modificar roles.');
        }

        if (! $workspace->hasUser($user)) {
            return redirect()->back()->withErrors([
                'workspace' => 'El usuario no pertenece a este workspace.',
            ]);
        }

        $existingRoles = $user->roles()
            ->where('roles.workspace_id', $workspace->id)
            ->get();

        foreach ($existingRoles as $role) {
            $user->removeRole($role);
        }

        foreach ($request->validated('role_ids') as $roleId) {
            $role = Role::find($roleId);

            if ($role && $role->workspace_id === $workspace->id) {
                app(AssignRoleAction::class)->handle($role, $user);
            }
        }

        return redirect()->back()->with('success', 'Roles actualizados exitosamente.');
    }
}
