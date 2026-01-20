<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionValidationException;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class CreateConfirmablePasswordAction
{
    public function handle(Request $request, User $user, string $password): void
    {
        if (! Auth::guard('web')->validate([
            'email' => $user->email,
            'password' => $password,
        ])) {
            throw new ActionValidationException([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());
    }
}
