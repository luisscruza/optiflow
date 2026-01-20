<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdatePasswordChangeAction;
use App\Http\Requests\UpdatePasswordChangeRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class PasswordChangeController
{
    public function edit(#[CurrentUser] User $user): Response
    {
        abort_unless($user->mustChangePassword(), 404);

        return Inertia::render('auth/new-password');
    }

    /**
     * Update the user's password.
     */
    public function update(UpdatePasswordChangeRequest $request, UpdatePasswordChangeAction $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated());

        return to_route('dashboard')->with('success', 'Contraseña actualizada con éxito.');
    }
}
