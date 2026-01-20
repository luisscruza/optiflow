<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\CreateAuthenticatedSessionAction;
use App\Actions\DeleteAuthenticatedSessionAction;
use App\Http\Requests\Auth\DeleteAuthenticatedSessionRequest;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

final class AuthenticatedSessionController
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, CreateAuthenticatedSessionAction $action): RedirectResponse
    {
        $action->handle($request);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(DeleteAuthenticatedSessionRequest $request, DeleteAuthenticatedSessionAction $action): RedirectResponse
    {
        $action->handle($request);

        return redirect('/login');
    }
}
