<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final class WorkspaceContextController extends Controller
{
    /**
     * Switch to a workspace (update action).
     */
    public function update(
        #[CurrentUser] User $user,
        Workspace $workspace
    ): RedirectResponse {
        if (! $user->switchToWorkspace($workspace)) {
            return back()->with('error', 'You do not have access to this workspace.');
        }

        return to_route('dashboard')->with('success', "Switched to {$workspace->name} workspace.");
    }

    /**
     * Leave a workspace (destroy action).
     */
    public function destroy(
        #[CurrentUser] User $user,
        Workspace $workspace
    ): RedirectResponse {
        if ($workspace->owner_id === $user->id) {
            return back()->with('error', 'You cannot leave a workspace you own. Transfer ownership or delete the workspace instead.');
        }

        if ($user->current_workspace_id === $workspace->id) {
            $anotherWorkspace = $user->workspaces()
                ->where('workspace_id', '!=', $workspace->id)
                ->first();

            $user->current_workspace_id = $anotherWorkspace?->id ?? null;
            $user->save();
        }

        $workspace->removeUser($user);

        return back()->with('success', "Left {$workspace->name} workspace.");
    }
}
