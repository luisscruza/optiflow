<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AcceptInvitationAction;
use App\Actions\AssignUserToWorkspaceAction;
use App\Enums\UserRole;
use App\Http\Requests\AssignUserToWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceInvitationRequest;
use App\Models\User;
use App\Models\UserInvitation;
use Exception;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class WorkspaceInvitationController
{
    /**
     * Assign a user to workspace(s). Create user if they don't exist.
     */
    public function store(AssignUserToWorkspaceRequest $request, AssignUserToWorkspaceAction $assignUserAction, #[CurrentUser] ?User $user): RedirectResponse
    {
        $workspaceAssignments = collect($request->validated('workspace_assignments'))
            ->map(fn (array $assignment): array => [
                'workspace_id' => $assignment['workspace_id'],
                'role' => UserRole::from($assignment['role']),
            ])
            ->toArray();

        $assignedUser = $assignUserAction->handle(
            email: $request->validated('email'),
            name: $request->validated('name'),
            password: $request->validated('password'),
            workspaceAssignments: $workspaceAssignments,
            assignedBy: $user,
        );

        $workspaceNames = collect($workspaceAssignments)
            ->map(fn ($assignment) => \App\Models\Workspace::query()->find($assignment['workspace_id'])->name)
            ->join(', ', ' y ');

        return redirect()->back()->with(
            'success',
            'Usuario '.$assignedUser->name.' asignado exitosamente a: '.$workspaceNames.'.'
        );
    }

    /**
     * Show the invitation acceptance page.
     */
    public function show(string $token): Response
    {
        $invitation = UserInvitation::query()->where('token', $token)
            ->with(['workspace', 'invitedBy'])
            ->firstOrFail();

        if (! $invitation->isPending()) {
            return Inertia::render('invitations/invalid', [
                'message' => $invitation->isExpired()
                    ? 'Esta invitación ha expirado.'
                    : 'Esta invitación ya no es válida.',
            ]);
        }

        return Inertia::render('invitations/accept', [
            'invitation' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'role' => $invitation->role->label(),
                'workspace' => [
                    'name' => $invitation->workspace->name,
                    'description' => $invitation->workspace->description,
                ],
                'invited_by' => [
                    'name' => $invitation->invitedBy->name,
                ],
                'expires_at' => $invitation->expires_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Accept an invitation.
     */
    public function update(UpdateWorkspaceInvitationRequest $request, string $token, AcceptInvitationAction $acceptInvitationAction): RedirectResponse
    {
        $invitation = UserInvitation::query()->where('token', $token)->firstOrFail();

        if (! $invitation->isPending()) {
            return redirect()->route('invitations.show', $token)->withErrors([
                'invitation' => $invitation->isExpired()
                    ? 'Esta invitación ha expirado.'
                    : 'Esta invitación ya no es válida.',
            ]);
        }

        if (! Auth::check()) {
            $existingUser = User::query()->where('email', $invitation->email)->first();

            if ($existingUser) {
                return redirect()->route('login')->with([
                    'invitation_token' => $token,
                    'message' => 'Por favor inicia sesión para aceptar la invitación.',
                ]);
            }

            return redirect()->route('register')->with([
                'invitation_token' => $token,
                'invitation_email' => $invitation->email,
                'message' => 'Crea tu cuenta para aceptar la invitación.',
            ]);
        }

        $user = Auth::user();

        // Verify the invitation email matches the logged-in user
        if ($user->email !== $invitation->email) {
            return redirect()->route('invitations.show', $token)->withErrors([
                'invitation' => 'Esta invitación fue enviada a '.$invitation->email.'. Debes iniciar sesión con esa cuenta para aceptarla.',
            ]);
        }

        try {
            $acceptInvitationAction->handle($invitation, $user);

            return redirect()->route('dashboard')->with(
                'success',
                'Te has unido al workspace '.$invitation->workspace->name.' exitosamente.'
            );
        } catch (Exception $e) {
            return redirect()->route('invitations.show', $token)->withErrors([
                'invitation' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Decline an invitation.
     */
    public function destroy(string $token): RedirectResponse
    {
        $invitation = UserInvitation::query()->where('token', $token)->firstOrFail();

        if (! $invitation->isPending()) {
            return redirect()->route('invitations.show', $token)->withErrors([
                'invitation' => 'Esta invitación ya no es válida.',
            ]);
        }

        $invitation->decline();

        return redirect()->route('home')->with(
            'info',
            'Has rechazado la invitación al workspace '.$invitation->workspace->name.'.'
        );
    }
}
