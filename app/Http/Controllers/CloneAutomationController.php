<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CloneAutomationAction;
use App\Http\Requests\CloneAutomationRequest;
use App\Models\Automation;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;

final class CloneAutomationController extends Controller
{
    public function __invoke(CloneAutomationRequest $request, Automation $automation, CloneAutomationAction $action): RedirectResponse
    {
        $targetWorkspace = Workspace::query()->findOrFail($request->integer('target_workspace_id'));

        $action->handle($automation, $request->user(), $targetWorkspace);

        return redirect()->route('automations.index')
            ->with('success', sprintf(
                'Automatizacion clonada en %s. Se creo como inactiva para que puedas revisarla antes de activarla.',
                $targetWorkspace->name,
            ));
    }
}
