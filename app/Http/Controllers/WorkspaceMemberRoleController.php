<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateMemberRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Context;
use Spatie\Permission\Models\Role;

final class WorkspaceMemberRoleController extends Controller
{
    /**
     * Update a member's role.
     */
    public function update(UpdateMemberRoleRequest $request, User $member): RedirectResponse
    {
        $workspace = Context::get('workspace');

        if (! $workspace->hasUser($member)) {
            abort(404);
        }

        $roleId = $request->validated('role');
        $role = Role::query()
            ->where('id', $roleId)
            ->where('workspace_id', $workspace->id)
            ->first();

        if (! $role) {
            return redirect()->back()->withErrors([
                'role' => 'El rol seleccionado no es válido.',
            ]);
        }

        // Remove existing roles for this workspace
        $existingRoles = $member->roles()
            ->where('roles.workspace_id', $workspace->id)
            ->get();

        foreach ($existingRoles as $existingRole) {
            $member->removeRole($existingRole);
        }

        // Assign new role
        $member->assignRole($role);

        return redirect()->back()->with(
            'success',
            'Rol de '.$member->name.' actualizado exitosamente.'
        );
    }
}
