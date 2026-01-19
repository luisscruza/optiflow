<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateWorkflowAction;
use App\Actions\DeleteWorkflowAction;
use App\Actions\UpdateWorkflowAction;
use App\Enums\Permission;
use App\Filters\WorkflowJob\ContactFilter;
use App\Filters\WorkflowJob\DateRangeFilter;
use App\Filters\WorkflowJob\DueStatusFilter;
use App\Filters\WorkflowJob\PriorityFilter;
use App\Filters\WorkflowJob\WorkspaceFilter;
use App\Http\Requests\CreateWorkflowRequest;
use App\Http\Requests\ShowWorkflowRequest;
use App\Http\Requests\UpdateWorkflowRequest;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Mastertable;
use App\Models\Prescription;
use App\Models\Workflow;
use App\Models\WorkflowJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
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
        abort_unless($request->user()->can(Permission::ViewWorkflows), 403);

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

    public function create(Request $request): Response
    {
        abort_unless($request->user()->can(Permission::CreateWorkflows), 403);

        return Inertia::render('workflows/create', [
            'mastertables' => Mastertable::query()->orderBy('name')->get(),
        ]);
    }

    public function store(CreateWorkflowRequest $request, CreateWorkflowAction $action): RedirectResponse
    {
        abort_unless($request->user()->can(Permission::CreateWorkflows), 403);

        $workflow = $action->handle($request->validated());

        return redirect()->route('workflows.show', $workflow)
            ->with('success', 'Flujo de trabajo creado exitosamente.');
    }

    public function show(ShowWorkflowRequest $request, Workflow $workflow): Response
    {

        $filters = $request->getFilters();
        $showAllWorkspaces = $request->showAllWorkspaces();

        $filterPipeline = [
            new WorkspaceFilter($showAllWorkspaces),
            new ContactFilter($filters['contact_id']),
            new PriorityFilter($filters['priority']),
            new DueStatusFilter($filters['due_status']),
            new DateRangeFilter($filters['date_from'], $filters['date_to']),
        ];

        $workflow->load([
            'stages' => fn($query) => $query->orderBy('position')->withCount([
                'jobs as pending_jobs_count' => function ($q) use ($filterPipeline, $filters) {
                    $this->applyPipelineFilters($q, $filterPipeline);
                    if (! $filters['status'] || $filters['status'] === 'pending') {
                        $q->whereNull('completed_at')->whereNull('canceled_at');
                    } elseif ($filters['status'] === 'completed') {
                        $q->whereNotNull('completed_at');
                    } elseif ($filters['status'] === 'canceled') {
                        $q->whereNotNull('canceled_at');
                    }
                },
                'jobs as completed_jobs_count' => function ($q) use ($filterPipeline) {
                    $this->applyPipelineFilters($q, $filterPipeline);
                    $q->whereNotNull('completed_at');
                },
            ]),
            'fields' => fn($query) => $query->where('is_active', true)->orderBy('position'),
            'fields.mastertable.items',
        ]);

        $stageJobProps = [];
        foreach ($workflow->stages as $stage) {
            $propName = 'stage_' . str_replace('-', '_', $stage->id) . '_jobs';

            $query = WorkflowJob::query()
                ->where('workflow_stage_id', $stage->id)
                ->with(['workspace', 'contact', 'invoice.contact', 'prescription.patient', 'media']);

            $query = app(Pipeline::class)
                ->send($query)
                ->through($filterPipeline)
                ->thenReturn();

            if ($filters['status'] === 'pending') {
                $query->whereNull('completed_at')->whereNull('canceled_at');
            } elseif ($filters['status'] === 'completed') {
                $query->whereNotNull('completed_at');
            } elseif ($filters['status'] === 'canceled') {
                $query->whereNotNull('canceled_at');
            }

            $stageJobProps[$propName] = Inertia::scroll(
                $query->orderBy('created_at', 'desc')
                    ->cursorPaginate(5, ['*'], $propName)
            );
        }

        return Inertia::render('workflows/show', array_merge([
            'workflow' => $workflow,
            'filters' => array_filter($filters),
            'showAllWorkspaces' => $showAllWorkspaces,
            'contacts' => fn() => Contact::query()
                ->customers()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
            'invoices' => Inertia::lazy(fn() => $request->getContactId()
                ? Invoice::query()
                ->where('contact_id', $request->getContactId())
                ->with('contact')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get()
                : []),
            'prescriptions' => Inertia::lazy(fn() => $request->getContactId()
                ? Prescription::query()
                ->with('patient')
                ->where('patient_id', $request->getContactId())
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get()
                : []),
        ], $stageJobProps));
    }

    public function edit(Request $request, Workflow $workflow): Response
    {
        abort_unless($request->user()->can(Permission::EditWorkflows), 403);

        $workflow->load(['fields' => fn($query) => $query->orderBy('position')]);

        return Inertia::render('workflows/edit', [
            'workflow' => $workflow,
            'mastertables' => Mastertable::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateWorkflowRequest $request, Workflow $workflow, UpdateWorkflowAction $action): RedirectResponse
    {
        abort_unless($request->user()->can(Permission::EditWorkflows), 403);

        $action->handle($workflow, $request->validated());

        return redirect()->route('workflows.show', $workflow)
            ->with('success', 'Flujo de trabajo actualizado exitosamente.');
    }

    public function destroy(Request $request, Workflow $workflow, DeleteWorkflowAction $action): RedirectResponse
    {
        abort_unless($request->user()->can(Permission::DeleteWorkflows), 403);

        $action->handle($workflow);

        return redirect()->route('workflows.index')
            ->with('success', 'Flujo de trabajo eliminado exitosamente.');
    }

    /**
     * Apply pipeline filters to a query.
     */
    private function applyPipelineFilters($query, array $filterPipeline): void
    {
        app(Pipeline::class)
            ->send($query)
            ->through($filterPipeline)
            ->thenReturn();
    }
}
