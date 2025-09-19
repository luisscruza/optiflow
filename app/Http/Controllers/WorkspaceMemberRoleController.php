<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateWorkspaceMemberRoleAction;
use App\Enums\UserRole;
use App\Http\Requests\UpdateMemberRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Context;

final class WorkspaceMemberRoleController extends Controller
{
    /**
     * Update a member's role.
     */
    public function update(UpdateMemberRoleRequest $request, User $member, UpdateWorkspaceMemberRoleAction $updateWorkspaceMemberRoleAction): RedirectResponse
    {
        $workspace = Context::get('workspace');

        if (! $workspace->hasUser($member)) {
            abort(404);
        }

        $updateWorkspaceMemberRoleAction->handle(
            workspace: $workspace,
            member: $member,
            newRole: UserRole::from($request->validated('role'))
        );

        return redirect()->back()->with('success',
            'Rol de '.$member->name.' actualizado exitosamente.'
        );
    }
}
