<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateWorkspaceMemberRoleAssignmentAction;
use App\Exceptions\ActionNotFoundException;
use App\Exceptions\ActionValidationException;
use App\Http\Requests\UpdateMemberRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Context;

final class WorkspaceMemberRoleController
{
    /**
     * Update a member's role.
     */
    public function update(UpdateMemberRoleRequest $request, User $member, UpdateWorkspaceMemberRoleAssignmentAction $action): RedirectResponse
    {
        $workspace = Context::get('workspace');

        try {
            $action->handle($workspace, $member, $request->validated('role'));
        } catch (ActionNotFoundException) {
            abort(404);
        } catch (ActionValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors());
        }

        return redirect()->back()->with(
            'success',
            'Rol de '.$member->name.' actualizado exitosamente.'
        );
    }
}
