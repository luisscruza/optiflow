<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\CreateNewPasswordAction;
use App\Exceptions\ActionValidationException;
use App\Http\Requests\Auth\CreateNewPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class NewPasswordController
{
    /**
     * Show the password reset page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(CreateNewPasswordRequest $request, CreateNewPasswordAction $action): RedirectResponse
    {
        try {
            $status = $action->handle($request->validated());

            return to_route('login')->with('status', __($status));
        } catch (ActionValidationException $exception) {
            throw ValidationException::withMessages(
                collect($exception->errors())
                    ->map(fn (string $message): array => [$message])
                    ->all()
            );
        }
    }
}
