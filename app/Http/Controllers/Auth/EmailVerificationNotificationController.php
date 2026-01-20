<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\CreateEmailVerificationNotificationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CreateEmailVerificationNotificationRequest;
use Illuminate\Http\RedirectResponse;

final class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(CreateEmailVerificationNotificationRequest $request, CreateEmailVerificationNotificationAction $action): RedirectResponse
    {
        $alreadyVerified = $action->handle($request->user());

        if ($alreadyVerified) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return back()->with('status', 'verification-link-sent');
    }
}
