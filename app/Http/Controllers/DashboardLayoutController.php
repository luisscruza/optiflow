<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class DashboardLayoutController extends Controller
{
    /**
     * Store the dashboard layout for the authenticated user.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'layout' => ['required', 'array'],
            'layout.*.id' => ['required', 'string'],
            'layout.*.x' => ['required', 'integer', 'min:0'],
            'layout.*.y' => ['required', 'integer', 'min:0'],
            'layout.*.w' => ['required', 'integer', 'min:1'],
            'layout.*.h' => ['required', 'integer', 'min:1'],
        ]);

        $user = Auth::user();
        $user->dashboard_layout = $validated['layout'];
        $user->save();

        return redirect()->back()->with('success', 'Tablero actualizado con éxito.');
    }
}
