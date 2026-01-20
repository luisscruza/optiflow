<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

final class CreateEmailVerificationNotificationAction
{
    public function handle(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return true;
        }

        $user->sendEmailVerificationNotification();

        return false;
    }
}
