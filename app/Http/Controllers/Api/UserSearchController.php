<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

final class UserSearchController extends Controller
{
    public function search(Request $request)
    {
        $email = $request->query('email');

        if (! $email) {
            return response()->json([]);
        }

        $users = User::query()->where('email', 'like', "%{$email}%")
            ->select('id', 'name', 'email')
            ->limit(10)
            ->get();

        return response()->json($users);
    }
}
