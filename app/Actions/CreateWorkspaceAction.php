<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\Workspace;
use App\Support\Slug;

final class CreateWorkspaceAction
{
    public function __construct() {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(User $user, array $validated): Workspace
    {
        $workspace = Workspace::create([
            'name' => $validated['name'],
            'slug' => Slug::generateUniqueSlug($validated['name'], Workspace::class),
            'description' => $validated['description'] ?? null,
            'owner_id' => $user->id,
            'is_active' => true,
        ]);

        $workspace->addUser($user, 'owner');

        $user->switchToWorkspace($workspace);

        return $workspace;
    }
}
