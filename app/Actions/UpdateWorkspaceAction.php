<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Workspace;

final class UpdateWorkspaceAction
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(Workspace $workspace, array $validated): Workspace
    {

        $workspace->update([
            ...$validated,
        ]);

        return $workspace->refresh();
    }
}
