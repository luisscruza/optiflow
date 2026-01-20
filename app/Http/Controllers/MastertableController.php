<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateMastertableAction;
use App\Actions\DeleteMastertableAction;
use App\Actions\UpdateMastertableAction;
use App\Enums\Permission;
use App\Http\Requests\CreateMastertableRequest;
use App\Http\Requests\UpdateMastertableRequest;
use App\Models\Mastertable;
use App\Models\User;
use App\Tables\MastertablesTable;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class MastertableController
{
    /**
     * Display a listing of mastertables.
     */
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::MastertablesView), 403);

        return Inertia::render('mastertables/index', [
            'mastertables' => MastertablesTable::make($request),
        ]);
    }

    /**
     * Show the form for creating a new mastertable.
     */
    public function create(#[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::MastertablesCreate), 403);

        return Inertia::render('mastertables/create');
    }

    /**
     * Store a newly created mastertable in storage.
     */
    public function store(CreateMastertableRequest $request, CreateMastertableAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::MastertablesCreate), 403);

        $mastertable = $action->handle($user, $request->validated());

        return redirect()->route('mastertables.show', $mastertable)
            ->with('success', 'Tabla maestra creada correctamente.');
    }

    /**
     * Display the specified mastertable.
     */
    public function show(Mastertable $mastertable, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::MastertablesView), 403);

        $mastertable->loadCount('items');
        $mastertable->load('items');

        return Inertia::render('mastertables/show', [
            'mastertable' => $mastertable,
        ]);
    }

    /**
     * Show the form for editing the specified mastertable.
     */
    public function edit(Mastertable $mastertable, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::MastertablesEdit), 403);

        $mastertable->loadCount('items');
        $mastertable->load('items');

        return Inertia::render('mastertables/edit', [
            'mastertable' => $mastertable,
        ]);
    }

    /**
     * Update the specified mastertable in storage.
     */
    public function update(UpdateMastertableRequest $request, Mastertable $mastertable, UpdateMastertableAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::MastertablesEdit), 403);

        $action->handle($user, $mastertable, $request->validated());

        return redirect()->route('mastertables.show', $mastertable)
            ->with('success', 'Tabla maestra actualizada correctamente.');
    }

    /**
     * Remove the specified mastertable from storage.
     */
    public function destroy(Mastertable $mastertable, DeleteMastertableAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::MastertablesDelete), 403);

        $action->handle($user, $mastertable);

        return redirect()->route('mastertables.index')
            ->with('success', 'Tabla maestra eliminada correctamente.');
    }
}
