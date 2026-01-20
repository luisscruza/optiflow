<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class DeleteAuthenticatedSessionAction
{
    public function handle(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
