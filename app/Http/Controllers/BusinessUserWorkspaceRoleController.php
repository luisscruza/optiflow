<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\UpdateUserWorkspaceRolesRequest;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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

        if (! in_array($currentUser->business_role, [UserRole::Owner, UserRole::Admin])) {
            abort(403, 'Solo el propietario del negocio puede modificar roles.');
        }

        if (! $workspace->hasUser($user)) {
            return redirect()->back()->withErrors([
                'workspace' => 'El usuario no pertenece a este workspace.',
            ]);
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);

        $user->roles()
            ->where('roles.workspace_id', $workspace->id)
            ->each(function (Role $role) use ($user): void {
                $user->removeRole($role);
            });

        foreach ($request->validated('role_ids') as $roleId) {
            $role = Role::find($roleId);
            $roleWorkspaceId = $role?->getAttribute('workspace_id');

            if ($roleWorkspaceId !== null && (int) $roleWorkspaceId === $workspace->id) {
                $user->assignRole($role);
            }
        }

        return redirect()->back()->with('success', 'Roles actualizados exitosamente.');
    }
}
