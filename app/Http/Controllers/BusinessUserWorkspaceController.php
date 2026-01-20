<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AddBusinessUserToWorkspaceAction;
use App\Actions\RemoveBusinessUserFromWorkspaceAction;
use App\Enums\UserRole;
use App\Exceptions\ActionValidationException;
use App\Http\Requests\AddUserToWorkspaceRequest;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final class BusinessUserWorkspaceController extends Controller
{
    /**
     * Add user to a workspace.
     */
    public function store(
        AddUserToWorkspaceRequest $request,
        User $user,
        AddBusinessUserToWorkspaceAction $action,
        #[CurrentUser] User $currentUser
    ): RedirectResponse {
        // Check if user is business owner
        if (! in_array($currentUser->business_role, [UserRole::Owner, UserRole::Admin])) {
            abort(403, 'Solo el propietario del negocio puede agregar usuarios a workspaces.');
        }

        $workspace = Workspace::findOrFail($request->validated('workspace_id'));

        try {
            $action->handle($user, $workspace, $request->validated('role_id'));
        } catch (ActionValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors());
        }

        return redirect()->back()->with('success', 'Usuario agregado al workspace exitosamente.');
    }

    /**
     * Remove user from a workspace.
     */
    public function destroy(User $user, Workspace $workspace, RemoveBusinessUserFromWorkspaceAction $action, #[CurrentUser] User $currentUser): RedirectResponse
    {
        // Check if user is business owner
        if (! in_array($currentUser->business_role, [UserRole::Owner, UserRole::Admin])) {
            abort(403, 'Solo el propietario del negocio puede remover usuarios de workspaces.');
        }

        try {
            $action->handle($user, $workspace);
        } catch (ActionValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors());
        }

        return redirect()->back()->with('success', 'Usuario removido del workspace exitosamente.');
    }
}
