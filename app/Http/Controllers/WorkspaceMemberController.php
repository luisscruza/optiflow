<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RemoveWorkspaceMemberAction;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Context;
use Inertia\Inertia;
use Inertia\Response;

final class WorkspaceMemberController extends Controller
{
    /**
     * Display workspace members.
     */
    public function index(#[CurrentUser] User $user): Response
    {

        $workspace = Context::get('workspace');

        $members = $workspace->users()
            ->withPivot(['role', 'joined_at'])
            ->orderBy('pivot_joined_at', 'desc')
            ->get();

        $availableWorkspaces = $user->workspaces()
            ->wherePivot('role', UserRole::Admin)
            ->orWhere('owner_id', $user->id)
            ->select('workspaces.id', 'workspaces.name')
            ->get()
            ->map(function ($ws) {
                return [
                    'id' => $ws->id,
                    'name' => $ws->name,
                ];
            });

        return Inertia::render('workspace/members', [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'is_owner' => $workspace->owner_id === $user->id,
            ],
            'members' => $members->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'role' => $member->pivot->role,
                    'role_label' => UserRole::from($member->pivot->role)->label(),
                    'joined_at' => $member->pivot->joined_at,
                ];
            }),
            'pending_invitations' => [],
            'roles' => UserRole::options(),
            'available_workspaces' => $availableWorkspaces,
        ]);
    }

    /**
     * Remove a member from the workspace.
     */
    public function destroy(User $member, RemoveWorkspaceMemberAction $removeMemberAction): RedirectResponse
    {
        $workspace = Context::get('workspace');

        if (! $workspace || ! $workspace->hasUser($member)) {
            abort(404);
        }

        if ($workspace->owner_id === $member->id) {
            return redirect()->back()->withErrors([
                'member' => 'No puedes remover al propietario de la sucursal.',
            ]);
        }

        $removeMemberAction->handle($workspace, $member);

        return redirect()->back()->with('success',
            $member->name.' ha sido removido de la sucursal exitosamente.'
        );
    }
}
