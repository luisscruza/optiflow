<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\InviteBusinessUserAction;
use App\Enums\UserRole;
use App\Exceptions\ActionValidationException;
use App\Http\Requests\InviteBusinessUserRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final class BusinessUserInvitationController extends Controller
{
    /**
     * Invite a new user to the business and assign them to workspaces.
     */
    public function store(
        InviteBusinessUserRequest $request,
        InviteBusinessUserAction $action,
        #[CurrentUser] User $currentUser
    ): RedirectResponse {
        // Check if user is business owner
        if (! in_array($currentUser->business_role, [UserRole::Owner, UserRole::Admin])) {
            abort(403, 'Solo el propietario del negocio puede invitar usuarios.');
        }

        try {
            $user = $action->handle($request->validated(), $currentUser);

            $workspaceNames = collect($request->validated('workspaces'))
                ->map(fn ($assignment) => \App\Models\Workspace::query()->find($assignment['workspace_id'])->name)
                ->join(', ', ' y ');

            return redirect()->back()->with(
                'success',
                'Usuario '.$user->name.' invitado exitosamente a: '.$workspaceNames.'.'
            );
        } catch (ActionValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors());
        }
    }
}
