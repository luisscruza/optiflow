<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

final class CreatePasswordResetLinkAction
{
    public function handle(array $data): void
    {
        DB::transaction(function () use ($data): void {
            Password::sendResetLink($data);
        });
    }
}
