<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateDashboardLayoutRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final class DashboardLayoutController extends Controller
{
    /**
     * Store the dashboard layout for the authenticated user.
     */
    public function store(CreateDashboardLayoutRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = Auth::user();
        $user->dashboard_layout = $validated['layout'];
        $user->save();

        return redirect()->back()->with('success', 'Tablero actualizado con éxito.');
    }
}
