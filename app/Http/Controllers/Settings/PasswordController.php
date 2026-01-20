<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\UpdateSettingsPasswordAction;
use App\Http\Requests\Settings\UpdateSettingsPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class PasswordController
{
    /**
     * Show the user's password settings page.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/password');
    }

    /**
     * Update the user's password.
     */
    public function update(UpdateSettingsPasswordRequest $request, UpdateSettingsPasswordAction $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated());

        return back();
    }
}
