<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RunAutomationTestAction;
use App\Exceptions\ActionNotFoundException;
use App\Models\Automation;
use App\Services\Automation\NodeRunners\NodeRunnerRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AutomationTestRunController
{
    /**
     * Run automation in test mode.
     */
    public function __invoke(Request $request, Automation $automation, NodeRunnerRegistry $registry, RunAutomationTestAction $action): JsonResponse
    {
        $validated = $request->validate([
            'job_id' => ['required', 'uuid'],
            'dry_run' => ['sometimes', 'boolean'],
        ]);

        try {
            $result = $action->handle($automation, $validated, $registry, $request->user());
        } catch (ActionNotFoundException $exception) {
            return response()->json(['error' => $exception->getMessage()], 404);
        }

        return response()->json($result);
    }
}
