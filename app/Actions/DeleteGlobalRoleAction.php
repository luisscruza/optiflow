<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionValidationException;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final readonly class DeleteGlobalRoleAction
{
    public function handle(string $roleName): void
    {
        DB::transaction(function () use ($roleName): void {
            $rolesWithUsers = Role::query()
                ->where('name', $roleName)
                ->get()
                ->filter(fn (Role $role): bool => $role->users()->count() > 0);

            if ($rolesWithUsers->isNotEmpty()) {
                throw new ActionValidationException([
                    'role' => 'No se puede eliminar un rol que está asignado a usuarios en algún workspace.',
                ]);
            }

            Role::query()
                ->where('name', $roleName)
                ->delete();
        });
    }
}
