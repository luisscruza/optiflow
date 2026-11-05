<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ResetClinicalPinAction;
use App\Actions\SetClinicalPinAction;
use App\Enums\UserRole;
use App\Exceptions\ActionValidationException;
use App\Http\Requests\UpdateClinicalPinRequest;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final class ContactClinicalPinController
{
    public function update(UpdateClinicalPinRequest $request, Contact $contact, SetClinicalPinAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        try {
            $action->handle($contact, $user, (string) $request->validated('pin'), $request->validated('current_pin'));
        } catch (ActionValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors());
        }

        return redirect()->back()->with('success', 'PIN clínico guardado.');
    }

    public function destroy(Contact $contact, ResetClinicalPinAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->hasBusinessRole(UserRole::Owner) || $user->hasBusinessRole(UserRole::Admin), 403);

        $action->handle($contact, $user);

        return redirect()->back()->with('success', 'PIN clínico restablecido. El evaluador deberá crear uno nuevo.');
    }
}
