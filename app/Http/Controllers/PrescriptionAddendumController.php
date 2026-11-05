<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AddPrescriptionAddendumAction;
use App\Exceptions\ActionValidationException;
use App\Http\Requests\StorePrescriptionAddendumRequest;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final class PrescriptionAddendumController
{
    public function store(StorePrescriptionAddendumRequest $request, Prescription $prescription, AddPrescriptionAddendumAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        try {
            $action->handle($prescription, $user, (string) $request->validated('content'), (string) $request->validated('pin'));
        } catch (ActionValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors());
        }

        return redirect()->back()->with('success', 'Adenda agregada.');
    }
}
