<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\CreateConfirmablePasswordAction;
use App\Exceptions\ActionValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CreateConfirmablePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class ConfirmablePasswordController extends Controller
{
    /**
     * Show the confirm password page.
     */
    public function show(): Response
    {
        return Inertia::render('auth/confirm-password');
    }

    /**
     * Confirm the user's password.
     */
    public function store(CreateConfirmablePasswordRequest $request, CreateConfirmablePasswordAction $action): RedirectResponse
    {
        try {
            $action->handle($request, $request->user(), $request->password);
        } catch (ActionValidationException $exception) {
            throw ValidationException::withMessages(
                collect($exception->errors())
                    ->map(fn (string $message): array => [$message])
                    ->all()
            );
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
