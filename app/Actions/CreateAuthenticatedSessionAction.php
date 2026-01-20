<?php

declare(strict_types=1);

namespace App\Actions;

use App\Http\Requests\Auth\LoginRequest;

final class CreateAuthenticatedSessionAction
{
    public function handle(LoginRequest $request): void
    {
        $request->authenticate();
        $request->session()->regenerate();
    }
}
