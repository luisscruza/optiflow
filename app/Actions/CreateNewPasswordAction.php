<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionValidationException;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

final class CreateNewPasswordAction
{
    public function handle(array $data): string
    {
        $status = DB::transaction(function () use ($data): string {
            return Password::reset(
                [
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'password_confirmation' => $data['password_confirmation'],
                    'token' => $data['token'],
                ],
                function (User $user) use ($data): void {
                    $user->forceFill([
                        'password' => Hash::make($data['password']),
                        'remember_token' => Str::random(60),
                    ])->save();

                    event(new PasswordReset($user));
                }
            );
        });

        if ($status !== Password::PasswordReset) {
            throw new ActionValidationException([
                'email' => __($status),
            ]);
        }

        return $status;
    }
}
