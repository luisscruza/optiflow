<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\Workspace;
use App\Support\Slug;

final readonly class CreateWorkspaceAction
{
    public function __construct(
        private SyncWorkspaceRoleAction $syncWorkspaceRoleAction
    ) {}

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
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'owner_id' => $user->id,
            'is_active' => true,
            'is_default' => $validated['is_personal'] ?? false,
        ]);

        $workspace->addUser($user, 'owner');

        $user->switchToWorkspace($workspace);

        // Sync all global roles to this new workspace
        $this->syncWorkspaceRoleAction->handle($workspace);

        return $workspace;
    }
}
