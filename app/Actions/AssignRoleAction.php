<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use DB;
use Spatie\Permission\Models\Role;

final readonly class AssignRoleAction
{
    /**
     * Execute the action.
     */
    public function handle(Role $role, User $user): void
    {
        DB::table('model_has_roles')
            ->insert([
                'role_id' => $role->id,
                'model_type' => 'user',
                'model_id' => $user->id,
                'workspace_id' => $role->workspace_id,
            ]);
    }
}
