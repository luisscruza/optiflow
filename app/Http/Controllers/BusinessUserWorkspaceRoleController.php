<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateBusinessUserWorkspaceRolesAction;
use App\Enums\UserRole;
use App\Exceptions\ActionValidationException;
use App\Http\Requests\UpdateUserWorkspaceRolesRequest;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final class BusinessUserWorkspaceRoleController extends Controller
{
    /**
     * Update user roles in a specific workspace.
     */
    public function update(
        UpdateUserWorkspaceRolesRequest $request,
        int $userId,
        int $workspaceId,
        UpdateBusinessUserWorkspaceRolesAction $action,
        #[CurrentUser] User $currentUser,
    ): RedirectResponse {
        $user = User::findOrFail($userId);
        $workspace = Workspace::findOrFail($workspaceId);

        if (! in_array($currentUser->business_role, [UserRole::Owner, UserRole::Admin])) {
            abort(403, 'Solo el propietario del negocio puede modificar roles.');
        }

        try {
            $action->handle($user, $workspace, $request->validated('role_ids'));
        } catch (ActionValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors());
        }

        return redirect()->back()->with('success', 'Roles actualizados exitosamente.');
    }
}
