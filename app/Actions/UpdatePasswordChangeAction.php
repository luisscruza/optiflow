<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class UpdatePasswordChangeAction
{
    public function handle(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data): void {
            $user->update([
                'password' => Hash::make($data['password']),
                'password_changed_at' => now(),
            ]);
        });
    }
}
