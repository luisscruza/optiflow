<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateUserPrimaryWorkspaceAction;
use App\Exceptions\ActionValidationException;
use App\Http\Requests\UpdateUserPrimaryWorkspaceRequest;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;

final class BusinessUserPrimaryWorkspaceController
{
    public function update(UpdateUserPrimaryWorkspaceRequest $request, User $user, UpdateUserPrimaryWorkspaceAction $action): RedirectResponse
    {
        $workspace = $request->filled('workspace_id')
            ? Workspace::query()->findOrFail($request->integer('workspace_id'))
            : null;

        try {
            $action->handle($user, $workspace);
        } catch (ActionValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors());
        }

        return redirect()->back()->with('success', 'Sucursal principal actualizada.');
    }
}
