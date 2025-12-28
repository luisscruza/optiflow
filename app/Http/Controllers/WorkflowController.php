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
use App\Models\Workflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class WorkflowController extends Controller
{
    /**
     * Display a listing of workflows.
     */
    public function index(Request $request): Response
    {
        $workflows = Workflow::query()
            ->withCount('stages')
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
     */
    public function show(Workflow $workflow): Response
    {
        $workflow->load([
            'stages' => fn ($query) => $query->orderBy('position'),
            'stages.jobs' => fn ($query) => $query->orderBy('created_at', 'desc'),
            'stages.jobs.invoice.contact',
            'stages.jobs.contact',
            'stages.jobs.comments.commentator',
        ]);

        return Inertia::render('workflows/show', [
            'workflow' => $workflow,
            'invoices' => Inertia::optional(fn () => Invoice::query()
                ->with('contact')
                ->whereNull('canceled_at')
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get()),
            'contacts' => Inertia::optional(fn () => Contact::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get()),
        ]);
    }

    /**
     * Show the form for editing the specified workflow.
     */
    public function edit(Workflow $workflow): Response
    {
        return Inertia::render('workflows/edit', [
            'workflow' => $workflow,
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
