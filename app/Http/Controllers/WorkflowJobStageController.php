<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\MoveWorkflowJobAction;
use App\Http\Requests\MoveWorkflowJobRequest;
use App\Models\Workflow;
use App\Models\WorkflowJob;
use App\Models\WorkflowStage;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

final class WorkflowJobStageController
{
    public function update(MoveWorkflowJobRequest $request, Workflow $workflow, WorkflowJob $job, MoveWorkflowJobAction $action): RedirectResponse
    {
        $validated = $request->validated();
        $targetStage = WorkflowStage::findOrFail($validated['workflow_stage_id']);

        try {
            $action->handle($job, $targetStage);

            return redirect()->back()
                ->with('success', 'Tarea movida exitosamente.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
