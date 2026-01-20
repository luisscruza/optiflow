<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class CreateRegisteredUserAction
{
    public function handle(array $data): User
    {
        $user = DB::transaction(function () use ($data): User {
            return User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);
        });

        event(new Registered($user));
        Auth::login($user);

        return $user;
    }
}
