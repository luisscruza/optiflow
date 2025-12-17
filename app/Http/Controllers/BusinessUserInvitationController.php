<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AssignUserToWorkspaceAction;
use App\Enums\UserRole;
use App\Http\Requests\InviteBusinessUserRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class BusinessUserInvitationController extends Controller
{
    /**
     * Invite a new user to the business and assign them to workspaces.
     */
    public function store(
        InviteBusinessUserRequest $request,
        AssignUserToWorkspaceAction $assignUserAction,
        #[CurrentUser] User $currentUser
    ): RedirectResponse {
        dd($currentUser->business_role);
        // Check if user is business owner
        if ($currentUser->business_role !== UserRole::Owner) {
            abort(403, 'Solo el propietario del negocio puede invitar usuarios.');
        }

        // Convert workspace assignments to the format expected by AssignUserToWorkspaceAction
        $workspaceAssignments = collect($request->validated('workspaces'))
            ->map(fn (array $assignment): array => [
                'workspace_id' => $assignment['workspace_id'],
                'role' => UserRole::User, // Using employee role for workspace pivot
            ])
            ->toArray();

        // Generate temporary password for new users
        $tempPassword = 'temp-'.Str::random(12);

        try {
            $user = $assignUserAction->handle(
                email: $request->validated('email'),
                name: $request->validated('name'),
                password: $tempPassword,
                workspaceAssignments: $workspaceAssignments,
                businessRole: UserRole::User,
                assignedBy: $currentUser
            );

            // Now assign Spatie roles if provided
            foreach ($request->validated('workspaces') as $workspaceData) {
                if (isset($workspaceData['role_id'])) {
                    $role = \Spatie\Permission\Models\Role::find($workspaceData['role_id']);
                    if ($role && $role->workspace_id === $workspaceData['workspace_id']) {
                        // Set the workspace context before assigning the role
                        setPermissionsTeamId($workspaceData['workspace_id']);
                        $user->assignRole($role);
                    }
                }
            }

            $workspaceNames = collect($workspaceAssignments)
                ->map(fn ($assignment) => \App\Models\Workspace::query()->find($assignment['workspace_id'])->name)
                ->join(', ', ' y ');

            return redirect()->back()->with(
                'success',
                'Usuario '.$user->name.' invitado exitosamente a: '.$workspaceNames.'.'
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['email' => $e->getMessage()]);
        }
    }
}
