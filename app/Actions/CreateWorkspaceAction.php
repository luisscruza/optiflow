<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Str;

final class CreateWorkspaceAction
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(User $user, array $validated): Workspace
    {
        $workspace = Workspace::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'owner_id' => $user->id,
            'is_active' => true,
        ]);

        $workspace->addUser($user, 'owner');

        $user->switchToWorkspace($workspace);

        return $workspace;
    }
}
