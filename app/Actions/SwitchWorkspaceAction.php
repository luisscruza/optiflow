<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

final class SwitchWorkspaceAction
{
    public function handle(User $user, Workspace $workspace): bool
    {
        return DB::transaction(function () use ($user, $workspace): bool {
            return $user->switchToWorkspace($workspace);
        });
    }
}
