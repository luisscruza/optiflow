<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\Mastertable;
use App\Models\MastertableItem;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class MastertableItemController extends Controller
{
    /**
     * Store a newly created item in storage.
     */
    public function store(Request $request, Mastertable $mastertable, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::MastertablesEdit), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
        ]);

        $mastertable->items()->create($validated);

        return redirect()->back()
            ->with('success', 'Elemento agregado correctamente.');
    }

    /**
     * Update the specified item in storage.
     */
    public function update(Request $request, Mastertable $mastertable, MastertableItem $item, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::MastertablesEdit), 403);

        abort_unless($item->mastertable_id === $mastertable->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
        ]);

        $item->update($validated);

        return redirect()->back()
            ->with('success', 'Elemento actualizado correctamente.');
    }

    /**
     * Remove the specified item from storage.
     */
    public function destroy(Mastertable $mastertable, MastertableItem $item, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::MastertablesEdit), 403);

        abort_unless($item->mastertable_id === $mastertable->id, 404);

        $item->delete();

        return redirect()->back()
            ->with('success', 'Elemento eliminado correctamente.');
    }
}
