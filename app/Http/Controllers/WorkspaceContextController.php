<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\LeaveWorkspaceAction;
use App\Actions\SwitchWorkspaceAction;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final class WorkspaceContextController
{
    /**
     * Switch to a workspace (update action).
     */
    public function update(
        #[CurrentUser] User $user,
        Workspace $workspace,
        SwitchWorkspaceAction $action
    ): RedirectResponse {
        if (! $action->handle($user, $workspace)) {
            return back()->with('error', 'You do not have access to this workspace.');
        }

        $workspace = $user->refresh()->currentWorkspace;

        return redirect()->route('dashboard')
            ->with('success', "Ahora estás en {$workspace->name}.");
    }

    /**
     * Leave a workspace (destroy action).
     */
    public function destroy(
        #[CurrentUser] User $user,
        Workspace $workspace,
        LeaveWorkspaceAction $action
    ): RedirectResponse {
        if ($workspace->owner_id === $user->id) {
            return back()->with('error', 'You cannot leave a workspace you own. Transfer ownership or delete the workspace instead.');
        }

        $action->handle($user, $workspace);

        return back()->with('success', "Left {$workspace->name} workspace.");
    }
}
