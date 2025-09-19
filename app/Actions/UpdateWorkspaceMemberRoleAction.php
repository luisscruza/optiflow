<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

final class UpdateWorkspaceMemberRoleAction
{
    /**
     * Update a member's role in a workspace.
     */
    public function handle(Workspace $workspace, User $member, UserRole $newRole): void
    {
        DB::transaction(function () use ($workspace, $member, $newRole): void {
            $workspace->users()->updateExistingPivot($member->id, [
                'role' => $newRole->value,
                'updated_at' => now(),
            ]);
        });

    }
}
