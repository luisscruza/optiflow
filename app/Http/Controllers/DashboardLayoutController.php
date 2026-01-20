<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateDashboardLayoutAction;
use App\Http\Requests\CreateDashboardLayoutRequest;
use Illuminate\Http\RedirectResponse;

final class DashboardLayoutController
{
    /**
     * Store the dashboard layout for the authenticated user.
     */
    public function store(CreateDashboardLayoutRequest $request, CreateDashboardLayoutAction $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated());

        return redirect()->back()->with('success', 'Tablero actualizado con éxito.');
    }
}
