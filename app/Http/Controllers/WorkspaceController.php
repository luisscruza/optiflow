<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateWorkspaceAction;
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

final class WorkspaceController
{
    /**
     * Display a listing of the resource.
     */
    public function index(#[CurrentUser] User $user): Response
    {
        return Inertia::render('workspaces/index', [
            'workspaces' => $user->workspaces()->with(['owner'])->get(),
            'ownedWorkspaces' => $user->ownedWorkspaces()->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateWorkspaceRequest $request, CreateWorkspaceAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        $workspace = $action->handle($user, $request->validated());

        Context::add('workspace', $workspace);

        return redirect()->route('workspaces.index')
            ->with('success', '¡Sucursal creada correctamente!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWorkspaceRequest $request, UpdateWorkspaceAction $action, #[CurrentUser] User $user, Workspace $workspace): RedirectResponse
    {
        $action->handle($workspace, $request->validated());

        return redirect()->back()
            ->with('success', 'Workspace updated successfully!');
    }
}
