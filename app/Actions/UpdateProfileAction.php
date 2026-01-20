<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateProfileAction
{
    public function handle(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data): void {
            $user->fill($data);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();
        });
    }
}
