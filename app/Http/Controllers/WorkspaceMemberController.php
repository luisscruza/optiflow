<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RemoveWorkspaceMemberAction;
use App\Exceptions\ActionNotFoundException;
use App\Exceptions\ActionValidationException;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Context;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

final class WorkspaceMemberController
{
    /**
     * Display workspace members.
     */
    public function index(#[CurrentUser] User $user): Response
    {
        $workspace = Context::get('workspace');

        $members = $workspace->users()
            ->withPivot(['joined_at'])
            ->orderBy('pivot_joined_at', 'desc')
            ->get();

        // Get all roles for the current workspace
        $workspaceRoles = Role::query()
            ->where('workspace_id', $workspace->id)
            ->get();

        $availableWorkspaces = $user->workspaces()
            ->wherePivot('role', 'admin')
            ->orWhere('owner_id', $user->id)
            ->select('workspaces.id', 'workspaces.name')
            ->get()
            ->map(fn ($ws): array => [
                'id' => $ws->id,
                'name' => $ws->name,
            ]);

        return Inertia::render('workspace/members', [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'is_owner' => $workspace->owner_id === $user->id,
            ],
            'members' => $members->map(function ($member) use ($workspace): array {
                // Get the user's role for this workspace
                $role = $member->roles()
                    ->where('roles.workspace_id', $workspace->id)
                    ->first();

                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'role' => $role?->name ?? 'Sin rol',
                    'role_id' => $role?->id,
                    'role_label' => $role?->name ?? 'Sin rol asignado',
                    'joined_at' => $member->pivot->joined_at,
                ];
            }),
            'pending_invitations' => [],
            'roles' => $workspaceRoles->mapWithKeys(fn (Role $role): array => [
                $role->id => $role->name,
            ])->toArray(),
            'available_workspaces' => $availableWorkspaces,
        ]);
    }

    /**
     * Remove a member from the workspace.
     */
    public function destroy(User $member, RemoveWorkspaceMemberAction $removeMemberAction): RedirectResponse
    {
        $workspace = Context::get('workspace');

        if (! $workspace) {
            abort(404);
        }

        try {
            $removeMemberAction->handle($workspace, $member);
        } catch (ActionNotFoundException) {
            abort(404);
        } catch (ActionValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors());
        }

        return redirect()->back()->with(
            'success',
            $member->name.' ha sido removido de la sucursal exitosamente.'
        );
    }
}
