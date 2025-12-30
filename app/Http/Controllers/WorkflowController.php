<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateWorkflowAction;
use App\Actions\DeleteWorkflowAction;
use App\Actions\UpdateWorkflowAction;
use App\Http\Requests\CreateWorkflowRequest;
use App\Http\Requests\UpdateWorkflowRequest;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Mastertable;
use App\Models\Prescription;
use App\Models\Workflow;
use App\Models\WorkflowJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Inertia\Inertia;
use Inertia\Response;

final class WorkflowController extends Controller
{
    /**
     * Display a listing of workflows.
     */
    public function index(Request $request): Response
    {
        $currentWorkspace = Context::get('workspace');

        $workflows = Workflow::query()
            ->withCount('stages')
            ->withCount(['stages as pending_jobs_count' => function ($query) use ($currentWorkspace) {
                $query->selectRaw('count(workflow_jobs.id)')
                    ->join('workflow_jobs', 'workflow_stages.id', '=', 'workflow_jobs.workflow_stage_id')
                    ->whereNull('workflow_jobs.completed_at')
                    ->whereNull('workflow_jobs.canceled_at')
                    ->where('workflow_jobs.workspace_id', $currentWorkspace->id);
            }])
            ->withCount(['stages as overdue_jobs_count' => function ($query) use ($currentWorkspace) {
                $query->selectRaw('count(workflow_jobs.id)')
                    ->join('workflow_jobs', 'workflow_stages.id', '=', 'workflow_jobs.workflow_stage_id')
                    ->whereNull('workflow_jobs.completed_at')
                    ->whereNull('workflow_jobs.canceled_at')
                    ->whereNotNull('workflow_jobs.due_date')
                    ->where('workflow_jobs.due_date', '<', now())
                    ->where('workflow_jobs.workspace_id', $currentWorkspace->id);
            }])
            ->orderBy('name')
            ->get();

        return Inertia::render('workflows/index', [
            'workflows' => $workflows,
        ]);
    }

    /**
     * Show the form for creating a new workflow.
     */
    public function create(): Response
    {
        return Inertia::render('workflows/create');
    }

    /**
     * Store a newly created workflow in storage.
     */
    public function store(CreateWorkflowRequest $request, CreateWorkflowAction $action): RedirectResponse
    {
        $workflow = $action->handle($request->validated());

        return redirect()->route('workflows.show', $workflow)
            ->with('success', 'Flujo de trabajo creado exitosamente.');
    }

    /**
     * Display the specified workflow (Kanban board view).
     * Jobs are paginated per stage via a separate endpoint for scalability.
     */
    public function show(Request $request, Workflow $workflow): Response
    {
        // Load workflow with stages (without jobs - they'll be loaded per-stage)
        $workflow->load([
            'stages' => fn($query) => $query->orderBy('position')->withCount([
                'jobs as pending_jobs_count' => fn($q) => $q->whereNull('completed_at')->whereNull('canceled_at'),
                'jobs as completed_jobs_count' => fn($q) => $q->whereNotNull('completed_at'),
            ]),
            'fields' => fn($query) => $query->where('is_active', true)->orderBy('position'),
            'fields.mastertable.items',
        ]);

        $stageJobProps = [];
        foreach ($workflow->stages as $stage) {
            $propName = 'stage_' . str_replace('-', '_', $stage->id) . '_jobs';
            $stageJobProps[$propName] = Inertia::scroll(
                WorkflowJob::query()
                    ->where('workflow_stage_id', $stage->id)
                    ->with(['workspace','contact', 'invoice.contact', 'prescription.patient'])
                    ->orderBy('created_at', 'desc')
                    ->cursorPaginate(5, ['*'], $propName)
            );
        }

        $contactId = $request->query('contact_id');

        return Inertia::render('workflows/show', array_merge([
            'workflow' => $workflow,
            'contacts' => fn() => Contact::query()
                ->customers()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
            'invoices' => Inertia::lazy(fn() => $contactId
                ? Invoice::query()
                ->with('contact')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get()
                : []),
            'prescriptions' => Inertia::lazy(fn() => $contactId
                ? Prescription::query()
                ->with('patient')
                ->where('patient_id', $contactId)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get()
                : []),
        ], $stageJobProps));
    }

    /**
     * Show the form for editing the specified workflow.
     */
    public function edit(Workflow $workflow): Response
    {
        $workflow->load(['fields' => fn($query) => $query->orderBy('position')]);

        return Inertia::render('workflows/edit', [
            'workflow' => $workflow,
            'mastertables' => Mastertable::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified workflow in storage.
     */
    public function update(UpdateWorkflowRequest $request, Workflow $workflow, UpdateWorkflowAction $action): RedirectResponse
    {
        $action->handle($workflow, $request->validated());

        return redirect()->route('workflows.show', $workflow)
            ->with('success', 'Flujo de trabajo actualizado exitosamente.');
    }

    /**
     * Remove the specified workflow from storage.
     */
    public function destroy(Workflow $workflow, DeleteWorkflowAction $action): RedirectResponse
    {
        $action->handle($workflow);

        return redirect()->route('workflows.index')
            ->with('success', 'Flujo de trabajo eliminado exitosamente.');
    }
}
