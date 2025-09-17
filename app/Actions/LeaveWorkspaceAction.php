<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

final readonly class LeaveWorkspaceAction
{
    /**
     * Execute the action.
     */
    public function handle(User $user, Workspace $workspace): void
    {
        DB::transaction(function () use ($user, $workspace): void {
            if ($user->current_workspace_id === $workspace->id) {
                $anotherWorkspace = $user->workspaces()
                    ->where('workspace_id', '!=', $workspace->id)
                    ->first();

                $user->update([
                    'current_workspace_id' => $anotherWorkspace?->id ?? null,
                ]);
            }

            $workspace->removeUser($user);
        });
    }
}
