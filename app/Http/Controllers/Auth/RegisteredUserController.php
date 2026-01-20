<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\CreateRegisteredUserAction;
use App\Http\Requests\Auth\CreateRegisteredUserRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class RegisteredUserController
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(CreateRegisteredUserRequest $request, CreateRegisteredUserAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
