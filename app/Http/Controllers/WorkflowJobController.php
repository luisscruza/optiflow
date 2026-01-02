<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateWorkflowJobAction;
use App\Actions\DeleteWorkflowJobAction;
use App\Actions\UpdateWorkflowJobAction;
use App\Http\Requests\CreateWorkflowJobRequest;
use App\Http\Requests\UpdateWorkflowJobRequest;
use App\Models\Workflow;
use App\Models\WorkflowJob;
use App\Models\WorkflowStage;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class WorkflowJobController extends Controller
{
    /**
     * Display the specified job.
     * Uses deferred props for events to optimize initial load.
     */
    public function show(Workflow $workflow, WorkflowJob $job): Response
    {
        // Load workflow with stages and fields (lightweight)
        $workflow->load([
            'stages' => fn ($query) => $query->orderBy('position'),
            'fields' => fn ($query) => $query->where('is_active', true)->orderBy('position'),
            'fields.mastertable.items',
        ]);

        // Eager load job relations
        $job->load([
            'workflow',
            'workflowStage',
            'contact',
            'invoice.contact',
            'invoice.items.product',
            'invoice.documentSubtype',
            'invoice.payments',
            'prescription.patient',
            'prescription.optometrist',
            'comments.commentator',
            'media',
        ]);

        return Inertia::render('workflow-jobs/show', [
            'workflow' => $workflow,
            'job' => $job,
            // Defer events loading - they're not critical for initial render
            'events' => Inertia::defer(fn () => $job->events()
                ->with(['user', 'fromStage', 'toStage'])
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get()),
        ]);
    }

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

        return redirect()->back()
            ->with('success', 'Tarea actualizada exitosamente.');
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
