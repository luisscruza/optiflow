<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Str;

final class UpdateWorkspaceAction
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(User $user, Workspace $workspace, array $validated): Workspace
    {
        // Check if user is owner or admin
        $userWorkspace = $workspace->users()->where('user_id', $user->id)->first();
        $userRole = $userWorkspace?->pivot?->role;

        if (! in_array($userRole, ['owner', 'admin'], true)) {
            abort(403, 'You do not have permission to edit this workspace.');
        }

        $workspace->name = $validated['name'];
        $workspace->slug = Str::slug($validated['name']);
        $workspace->description = $validated['description'] ?? null;
        $workspace->save();

        return $workspace;
    }
}
