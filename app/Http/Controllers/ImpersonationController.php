<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ImpersonateUserAction;
use App\Actions\StopImpersonatingUserAction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class ImpersonationController extends Controller
{
    public function store(User $user, ImpersonateUserAction $action): RedirectResponse
    {
        $action->handle($user);

        return to_route('dashboard')->with('success', 'Estás suplantando a '.$user->name.'.');
    }

    public function destroy(StopImpersonatingUserAction $action)
    {
        $action->handle();

        return redirect()->back()->with('success', 'Se ha finalizado la suplantación.');
    }
}
