<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\CreatePasswordResetLinkAction;
use App\Http\Requests\Auth\CreatePasswordResetLinkRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PasswordResetLinkController
{
    /**
     * Show the password reset link request page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(CreatePasswordResetLinkRequest $request, CreatePasswordResetLinkAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return back()->with('status', __('A reset link will be sent if the account exists.'));
    }
}
