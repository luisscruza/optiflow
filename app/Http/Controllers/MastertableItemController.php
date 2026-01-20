<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateMastertableItemAction;
use App\Actions\DeleteMastertableItemAction;
use App\Actions\UpdateMastertableItemAction;
use App\Enums\Permission;
use App\Exceptions\ActionNotFoundException;
use App\Http\Requests\CreateMastertableItemRequest;
use App\Http\Requests\UpdateMastertableItemRequest;
use App\Models\Mastertable;
use App\Models\MastertableItem;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final class MastertableItemController extends Controller
{
    /**
     * Store a newly created item in storage.
     */
    public function store(CreateMastertableItemRequest $request, Mastertable $mastertable, CreateMastertableItemAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::MastertablesEdit), 403);

        $action->handle($mastertable, $request->validated());

        return redirect()->back()
            ->with('success', 'Elemento agregado correctamente.');
    }

    /**
     * Update the specified item in storage.
     */
    public function update(UpdateMastertableItemRequest $request, Mastertable $mastertable, MastertableItem $item, UpdateMastertableItemAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::MastertablesEdit), 403);

        try {
            $action->handle($mastertable, $item, $request->validated());
        } catch (ActionNotFoundException) {
            abort(404);
        }

        return redirect()->back()
            ->with('success', 'Elemento actualizado correctamente.');
    }

    /**
     * Remove the specified item from storage.
     */
    public function destroy(Mastertable $mastertable, MastertableItem $item, DeleteMastertableItemAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::MastertablesEdit), 403);

        try {
            $action->handle($mastertable, $item);
        } catch (ActionNotFoundException) {
            abort(404);
        }

        return redirect()->back()
            ->with('success', 'Elemento eliminado correctamente.');
    }
}
