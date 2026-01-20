<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionNotFoundException;
use App\Exceptions\ActionValidationException;
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
            if (! $workspace->hasUser($member)) {
                throw new ActionNotFoundException('Member not found in workspace.');
            }

            if ($workspace->owner_id === $member->id) {
                throw new ActionValidationException([
                    'member' => 'No puedes remover al propietario de la sucursal.',
                ]);
            }

            $member->roles()
                ->where('roles.workspace_id', $workspace->id)
                ->detach();

            $workspace->users()->detach($member->id);

            if ($member->current_workspace_id === $workspace->id) {
                $member->current_workspace_id = null;
                $member->save();
            }
        });
    }
}
