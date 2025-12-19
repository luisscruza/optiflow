<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Spatie\Permission\Models\Role;

final readonly class AssignRoleAction
{
    /**
     * Execute the action.
     */
    public function handle(Role $role, User $user): void
    {
        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }
    }
}
