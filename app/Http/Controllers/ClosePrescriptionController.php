<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ClosePrescriptionAction;
use App\Exceptions\ActionValidationException;
use App\Http\Requests\ClosePrescriptionRequest;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final class ClosePrescriptionController
{
    public function __invoke(ClosePrescriptionRequest $request, Prescription $prescription, ClosePrescriptionAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        try {
            $action->handle($prescription, $user, (string) $request->validated('pin'), $request->validated('pin_confirmation'));
        } catch (ActionValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors());
        }

        return redirect()->back()->with('success', 'Historia clínica cerrada.');
    }
}
