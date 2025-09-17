<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateWorkspaceAction;
use App\Actions\DeleteWorkspaceAction;
use App\Actions\UpdateWorkspaceAction;
use App\Http\Requests\CreateWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Context;
use Inertia\Inertia;
use Inertia\Response;

final class WorkspaceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(#[CurrentUser] User $user): Response
    {
        return Inertia::render('workspaces/index', [
            'workspaces' => $user->workspaces()->with('owner')->get(),
            'ownedWorkspaces' => $user->ownedWorkspaces()->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('workspaces/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateWorkspaceRequest $request, CreateWorkspaceAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        $workspace = $action->handle($user, $request->validated());

        Context::add('workspace', $workspace);

        return redirect()->route('workspaces.index')
            ->with('success', 'Workspace created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(#[CurrentUser] User $user, Workspace $workspace): Response
    {
        // Check if user has access to this workspace
        if (! $user->hasAccessToWorkspace($workspace)) {
            abort(403, 'You do not have access to this workspace.');
        }

        return Inertia::render('workspaces/show', [
            'workspace' => $workspace->load(['owner', 'users']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(#[CurrentUser] User $user, Workspace $workspace): Response
    {
        $userWorkspace = $workspace->users()->where('user_id', $user->id)->first();

        $userRole = $userWorkspace?->pivot?->role;

        if (! in_array($userRole, ['owner', 'admin'], true)) {
            abort(403, 'You do not have permission to edit this workspace.');
        }

        return Inertia::render('workspaces/edit', [
            'workspace' => $workspace,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWorkspaceRequest $request, UpdateWorkspaceAction $action, #[CurrentUser] User $user, Workspace $workspace): RedirectResponse
    {
        $action->handle($user, $workspace, $request->validated());

        return redirect()->route('workspaces.show', $workspace)
            ->with('success', 'Workspace updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeleteWorkspaceAction $action, #[CurrentUser] User $user, Workspace $workspace): RedirectResponse
    {
        $action->handle($user, $workspace);

        return redirect()->route('workspaces.index')
            ->with('success', 'Workspace deleted successfully!');
    }
}
