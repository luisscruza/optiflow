<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateWorkflowStageAction;
use App\Actions\DeleteWorkflowStageAction;
use App\Actions\UpdateWorkflowStageAction;
use App\Http\Requests\CreateWorkflowStageRequest;
use App\Http\Requests\UpdateWorkflowStageRequest;
use App\Models\Workflow;
use App\Models\WorkflowStage;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

final class WorkflowStageController extends Controller
{
    /**
     * Store a newly created stage in storage.
     */
    public function store(CreateWorkflowStageRequest $request, Workflow $workflow, CreateWorkflowStageAction $action): RedirectResponse
    {
        $action->handle($workflow, $request->validated());

        return redirect()->route('workflows.show', $workflow)
            ->with('success', 'Etapa creada exitosamente.');
    }

    /**
     * Update the specified stage in storage.
     */
    public function update(UpdateWorkflowStageRequest $request, Workflow $workflow, WorkflowStage $stage, UpdateWorkflowStageAction $action): RedirectResponse
    {
        $action->handle($stage, $request->validated());

        return redirect()->route('workflows.show', $workflow)
            ->with('success', 'Etapa actualizada exitosamente.');
    }

    /**
     * Remove the specified stage from storage.
     */
    public function destroy(Workflow $workflow, WorkflowStage $stage, DeleteWorkflowStageAction $action): RedirectResponse
    {
        try {
            $action->handle($stage);

            return redirect()->route('workflows.show', $workflow)
                ->with('success', 'Etapa eliminada exitosamente.');
        } catch (RuntimeException $e) {
            return redirect()->route('workflows.show', $workflow)
                ->with('error', $e->getMessage());
        }
    }
}
