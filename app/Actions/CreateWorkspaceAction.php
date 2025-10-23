<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\Workspace;
use App\Support\Slug;

final class CreateWorkspaceAction
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(User $user, array $validated): Workspace
    {
        $workspace = Workspace::query()->create([
            'name' => $validated['name'],
            'slug' => Slug::generateUniqueSlug($validated['name'], Workspace::class),
            'code' => $validated['code'],
            'description' => $validated['description'] ?? null,
            'owner_id' => $user->id,
            'is_active' => true,
            'is_default' => $validated['is_personal'] ?? false,
        ]);

        $workspace->addUser($user, 'owner');

        $user->switchToWorkspace($workspace);

        return $workspace;
    }
}
