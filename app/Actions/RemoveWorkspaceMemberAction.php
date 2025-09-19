<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

final readonly class RemoveWorkspaceMemberAction
{
    /**
     * Execute the action.
     */
    public function handle(Workspace $workspace, User $member): void
    {
        DB::transaction(function () use ($workspace, $member): void {
            $workspace->users()->detach($member->id);

            if ($member->current_workspace_id === $workspace->id) {
                $member->current_workspace_id = null;
                $member->save();
            }
        });
    }
}
