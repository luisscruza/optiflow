<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateWorkflowJobAction;
use App\Actions\DeleteWorkflowJobAction;
use App\Actions\MoveWorkflowJobAction;
use App\Actions\UpdateWorkflowJobAction;
use App\Http\Requests\CreateWorkflowJobRequest;
use App\Http\Requests\MoveWorkflowJobRequest;
use App\Http\Requests\UpdateWorkflowJobRequest;
use App\Models\Workflow;
use App\Models\WorkflowJob;
use App\Models\WorkflowStage;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

final class WorkflowJobController extends Controller
{
    /**
     * Store a newly created job in storage.
     */
    public function store(CreateWorkflowJobRequest $request, Workflow $workflow, CreateWorkflowJobAction $action): RedirectResponse
    {
        $validated = $request->validated();
        $stage = WorkflowStage::findOrFail($validated['workflow_stage_id']);

        $action->handle($stage, $validated);

        return redirect()->route('workflows.show', $workflow)
            ->with('success', 'Tarea creada exitosamente.');
    }

    /**
     * Update the specified job in storage.
     */
    public function update(UpdateWorkflowJobRequest $request, Workflow $workflow, WorkflowJob $job, UpdateWorkflowJobAction $action): RedirectResponse
    {
        $action->handle($job, $request->validated());

        return redirect()->route('workflows.show', $workflow)
            ->with('success', 'Tarea actualizada exitosamente.');
    }

    /**
     * Move a job to a different stage.
     */
    public function move(MoveWorkflowJobRequest $request, Workflow $workflow, WorkflowJob $job, MoveWorkflowJobAction $action): RedirectResponse
    {
        $validated = $request->validated();
        $targetStage = WorkflowStage::findOrFail($validated['workflow_stage_id']);

        try {
            $action->handle($job, $targetStage);

            return redirect()->route('workflows.show', $workflow)
                ->with('success', 'Tarea movida exitosamente.');
        } catch (InvalidArgumentException $e) {
            return redirect()->route('workflows.show', $workflow)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified job from storage.
     */
    public function destroy(Workflow $workflow, WorkflowJob $job, DeleteWorkflowJobAction $action): RedirectResponse
    {
        $action->handle($job);

        return redirect()->route('workflows.show', $workflow)
            ->with('success', 'Tarea eliminada exitosamente.');
    }
}
