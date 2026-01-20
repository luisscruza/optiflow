<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class DeleteProfileAction
{
    public function handle(Request $request, User $user): void
    {
        Auth::logout();

        DB::transaction(function () use ($user): void {
            $user->delete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
