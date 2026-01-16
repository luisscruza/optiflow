<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAutomationRequest;
use App\Http\Requests\UpdateAutomationRequest;
use App\Models\Automation;
use App\Models\AutomationTrigger;
use App\Models\AutomationVersion;
use App\Models\TelegramBot;
use App\Models\WhatsappAccount;
use App\Models\Workflow;
use App\Models\WorkflowJob;
use App\Services\Automation\NodeRunners\NodeRunnerRegistry;
use App\Services\Automation\NodeTypes\NodeTypeRegistry;
use App\Services\Automation\Support\AutomationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final class AutomationController extends Controller
{
    public function index(): Response
    {
        $automations = Automation::query()
            ->with(['triggers'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(Automation $automation): array => [
                'id' => $automation->id,
                'name' => $automation->name,
                'is_active' => (bool) $automation->is_active,
                'published_version' => (int) $automation->published_version,
                'created_at' => optional($automation->created_at)?->toISOString(),
                'triggers' => $automation->triggers->map(fn(AutomationTrigger $trigger): array => [
                    'id' => $trigger->id,
                    'event_key' => $trigger->event_key,
                    'workflow_id' => $trigger->workflow_id,
                    'workflow_stage_id' => $trigger->workflow_stage_id,
                    'is_active' => (bool) $trigger->is_active,
                ])->all(),
            ]);

        return Inertia::render('automations/index', [
            'automations' => $automations,
        ]);
    }

    public function create(): Response
    {
        $workflows = Workflow::query()
            ->with(['stages' => fn($query) => $query->orderBy('position')])
            ->orderBy('name')
            ->get()
            ->map(fn(Workflow $workflow): array => [
                'id' => $workflow->id,
                'name' => $workflow->name,
                'stages' => $workflow->stages->map(fn($stage): array => [
                    'id' => $stage->id,
                    'name' => $stage->name,
                ])->all(),
            ]);

        $telegramBots = TelegramBot::query()
            ->where('workspace_id', Auth::user()->current_workspace_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'bot_username', 'default_chat_id']);

        $whatsappAccounts = WhatsappAccount::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'display_phone_number', 'business_account_id']);

        return Inertia::render('automations/create', [
            'workflows' => $workflows,
            'telegramBots' => $telegramBots,
            'whatsappAccounts' => $whatsappAccounts,
            'nodeTypeRegistry' => app(NodeTypeRegistry::class)->toGroupedArray(),
            'templateVariables' => $this->templateVariables(),
        ]);
    }

    public function store(StoreAutomationRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $triggerType = $validated['trigger_type'];

        $automation = Automation::query()->create([
            'workspace_id' => Auth::user()->current_workspace_id,
            'name' => $validated['name'],
            'is_active' => $validated['is_active'],
            'published_version' => 1,
        ]);

        $definition = $this->buildDefinitionFromVisualBuilder($validated);

        $version = AutomationVersion::query()->create([
            'automation_id' => $automation->id,
            'version' => 1,
            'definition' => $definition,
            'created_by' => Auth::id(),
        ]);

        $nodeTypeRegistry = app(NodeTypeRegistry::class);

        $triggerNode = collect($definition['nodes'] ?? [])->first(function ($node) use ($nodeTypeRegistry): bool {
            $type = $node['type'] ?? null;

            return is_array($node) && is_string($type) && $nodeTypeRegistry->get($type)?->category === 'trigger';
        });

        $triggerNodeType = is_array($triggerNode) && is_string($triggerNode['type'] ?? null)
            ? (string) $triggerNode['type']
            : $triggerType;

        $triggerConfig = is_array($triggerNode) ? ($triggerNode['config'] ?? []) : [];
        if (! is_array($triggerConfig)) {
            $triggerConfig = [];
        }

        $eventKey = $nodeTypeRegistry->getEventKeyForTrigger($triggerNodeType) ?? 'workflow.job.stage_changed';

        $workflowId = null;
        $stageId = null;
        if ($triggerNodeType === 'workflow.stage_entered') {
            $workflowId = $triggerConfig['workflow_id'] ?? ($validated['trigger_workflow_id'] ?? null);
            $stageId = $triggerConfig['stage_id'] ?? ($validated['trigger_stage_id'] ?? null);
        }

        AutomationTrigger::query()->create([
            'automation_id' => $automation->id,
            'workspace_id' => Auth::user()->current_workspace_id,
            'event_key' => $eventKey,
            'workflow_id' => $workflowId,
            'workflow_stage_id' => $stageId,
            'is_active' => true,
        ]);

        $automation->published_version = $version->version;
        $automation->save();

        return redirect()->route('automations.index')
            ->with('success', 'Automatización creada correctamente.');
    }

    public function edit(Automation $automation): Response
    {
        $automation->load(['triggers']);

        $version = AutomationVersion::query()
            ->where('automation_id', $automation->id)
            ->orderByDesc('version')
            ->first();

        $definition = $version?->definition ?? [];

        $workflows = Workflow::query()
            ->with(['stages' => fn($query) => $query->orderBy('position')])
            ->orderBy('name')
            ->get()
            ->map(fn(Workflow $workflow): array => [
                'id' => $workflow->id,
                'name' => $workflow->name,
                'stages' => $workflow->stages->map(fn($stage): array => [
                    'id' => $stage->id,
                    'name' => $stage->name,
                ])->all(),
            ]);

        $telegramBots = TelegramBot::query()
            ->where('workspace_id', Auth::user()->current_workspace_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'bot_username', 'default_chat_id']);

        $whatsappAccounts = WhatsappAccount::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'display_phone_number', 'business_account_id']);

        return Inertia::render('automations/edit', [
            'automation' => [
                'id' => $automation->id,
                'name' => $automation->name,
                'is_active' => (bool) $automation->is_active,
                'published_version' => (int) $automation->published_version,
            ],
            'trigger' => $automation->triggers->first() ? [
                'workflow_id' => $automation->triggers->first()->workflow_id,
                'workflow_stage_id' => $automation->triggers->first()->workflow_stage_id,
            ] : null,
            'definition' => $definition,
            'workflows' => $workflows,
            'telegramBots' => $telegramBots,
            'whatsappAccounts' => $whatsappAccounts,
            'nodeTypeRegistry' => app(NodeTypeRegistry::class)->toGroupedArray(),
            'templateVariables' => $this->templateVariables(),
        ]);
    }

    public function update(UpdateAutomationRequest $request, Automation $automation): RedirectResponse
    {
        $validated = $request->validated();
        $triggerType = $validated['trigger_type'];

        $automation->update([
            'name' => $validated['name'],
            'is_active' => $validated['is_active'],
        ]);

        $nextVersion = (int) (AutomationVersion::query()->where('automation_id', $automation->id)->max('version') ?? 0) + 1;

        $definition = $this->buildDefinitionFromVisualBuilder($validated);

        $version = AutomationVersion::query()->create([
            'automation_id' => $automation->id,
            'version' => $nextVersion,
            'definition' => $definition,
            'created_by' => Auth::id(),
        ]);

        $trigger = $automation->triggers()->first();

        $nodeTypeRegistry = app(NodeTypeRegistry::class);

        $triggerNode = collect($definition['nodes'] ?? [])->first(function ($node) use ($nodeTypeRegistry): bool {
            $type = $node['type'] ?? null;

            return is_array($node) && is_string($type) && $nodeTypeRegistry->get($type)?->category === 'trigger';
        });

        $triggerNodeType = is_array($triggerNode) && is_string($triggerNode['type'] ?? null)
            ? (string) $triggerNode['type']
            : $triggerType;

        $triggerConfig = is_array($triggerNode) ? ($triggerNode['config'] ?? []) : [];
        if (! is_array($triggerConfig)) {
            $triggerConfig = [];
        }

        $eventKey = $nodeTypeRegistry->getEventKeyForTrigger($triggerNodeType) ?? 'workflow.job.stage_changed';

        $workflowId = null;
        $stageId = null;
        if ($triggerNodeType === 'workflow.stage_entered') {
            $workflowId = $triggerConfig['workflow_id'] ?? ($validated['trigger_workflow_id'] ?? null);
            $stageId = $triggerConfig['stage_id'] ?? ($validated['trigger_stage_id'] ?? null);
        }

        if ($trigger) {
            $trigger->update([
                'event_key' => $eventKey,
                'workflow_id' => $workflowId,
                'workflow_stage_id' => $stageId,
                'is_active' => true,
            ]);
        } else {
            AutomationTrigger::query()->create([
                'automation_id' => $automation->id,
                'workspace_id' => Auth::user()->current_workspace_id,
                'event_key' => $eventKey,
                'workflow_id' => $workflowId,
                'workflow_stage_id' => $stageId,
                'is_active' => true,
            ]);
        }

        $automation->published_version = $version->version;
        $automation->save();

        return redirect()->route('automations.index')
            ->with('success', 'Automatización actualizada correctamente.');
    }

    /**
     * Get workflow jobs for test mode.
     */
    public function testData(Request $request): JsonResponse
    {
        $workflowId = $request->input('workflow_id');

        if (! $workflowId) {
            return response()->json(['jobs' => []]);
        }

        $jobs = WorkflowJob::query()
            ->with(['contact', 'invoice', 'stage'])
            ->where('workflow_id', $workflowId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn(WorkflowJob $job): array => [
                'id' => $job->id,
                'title' => $job->title,
                'contact_name' => $job->contact?->name ?? '—',
                'stage_name' => $job->stage?->name ?? '—',
                'created_at' => $job->created_at?->toDateTimeString(),
            ]);

        return response()->json(['jobs' => $jobs]);
    }

    /**
     * Run automation in test mode.
     */
    public function runTest(Request $request, Automation $automation, NodeRunnerRegistry $registry): JsonResponse
    {
        $validated = $request->validate([
            'job_id' => ['required', 'uuid'],
            'dry_run' => ['sometimes', 'boolean'],
        ]);

        $job = WorkflowJob::query()
            ->with(['contact', 'invoice', 'stage', 'workflow'])
            ->findOrFail($validated['job_id']);

        $version = AutomationVersion::query()
            ->where('automation_id', $automation->id)
            ->orderByDesc('version')
            ->first();

        if (! $version) {
            return response()->json(['error' => 'No hay versión publicada'], 404);
        }

        $definition = $version->definition;
        $nodes = $definition['nodes'] ?? [];
        $edges = $definition['edges'] ?? [];

        // Build context
        $context = new AutomationContext(
            job: $job,
            fromStageId: $job->workflow_stage_id,
            toStageId: $job->workflow_stage_id,
            actorId: Auth::id(),
        );

        $results = [];
        $dryRun = $validated['dry_run'] ?? true;

        // Build adjacency list for traversal
        $adjacency = [];
        foreach ($edges as $edge) {
            $from = $edge['from'] ?? '';
            $to = $edge['to'] ?? '';
            if ($from && $to) {
                $adjacency[$from][] = $to;
            }
        }

        // Index nodes by ID
        $nodeIndex = [];
        foreach ($nodes as $node) {
            $nodeIndex[$node['id']] = $node;
        }

        // Find the trigger node (starting point)
        $currentNodes = array_filter($nodes, fn($n) => $n['type'] === 'workflow.stage_entered');
        $queue = array_map(fn($n) => $n['id'], $currentNodes);

        $visited = [];
        $input = [];

        while (! empty($queue)) {
            $nodeId = array_shift($queue);

            if (in_array($nodeId, $visited, true)) {
                continue;
            }

            $visited[] = $nodeId;
            $nodeData = $nodeIndex[$nodeId] ?? null;

            if (! $nodeData) {
                continue;
            }

            $nodeType = $nodeData['type'];
            $config = $nodeData['config'] ?? [];

            $result = [
                'node_id' => $nodeId,
                'type' => $nodeType,
                'status' => 'skipped',
                'output' => null,
            ];

            // Skip trigger nodes in execution
            if ($nodeType === 'workflow.stage_entered') {
                $result['status'] = 'success';
                $result['output'] = ['message' => 'Trigger activado'];
                $results[] = $result;

                // Add all connected nodes to queue
                foreach ($adjacency[$nodeId] ?? [] as $nextId) {
                    $queue[] = $nextId;
                }

                continue;
            }

            // Check if runner exists
            if (! $registry->has($nodeType)) {
                $result['status'] = 'error';
                $result['output'] = ['error' => "Runner no encontrado para: {$nodeType}"];
                $results[] = $result;

                continue;
            }

            try {
                if ($dryRun) {
                    // In dry run, just show what would happen
                    $templateData = $context->toTemplateData($input);
                    $result['status'] = 'dry_run';
                    $result['output'] = [
                        'message' => 'Se ejecutaría este nodo',
                        'config' => $config,
                        'available_data' => array_keys($templateData),
                    ];
                } else {
                    // Actually run the node
                    $runner = $registry->get($nodeType);
                    $nodeResult = $runner->run($context, $config, $input);

                    $result['status'] = $nodeResult->success ? 'success' : 'error';
                    $result['output'] = $nodeResult->data;

                    // For condition nodes, determine which branch to follow
                    if ($nodeType === 'logic.condition' && $nodeResult->success) {
                        $branch = $nodeResult->data['branch'] ?? 'true';
                        // Filter next nodes by the branch handle
                        foreach ($edges as $edge) {
                            if (($edge['from'] ?? '') === $nodeId) {
                                $sourceHandle = $edge['sourceHandle'] ?? 'true';
                                if ($sourceHandle === $branch) {
                                    $queue[] = $edge['to'];
                                }
                            }
                        }
                        $results[] = $result;

                        continue;
                    }

                    $input = array_merge($input, $nodeResult->data);
                }
            } catch (Throwable $e) {
                $result['status'] = 'error';
                $result['output'] = ['error' => $e->getMessage()];
            }

            $results[] = $result;

            // Add next nodes to queue (for non-condition nodes)
            if ($nodeType !== 'logic.condition') {
                foreach ($adjacency[$nodeId] ?? [] as $nextId) {
                    $queue[] = $nextId;
                }
            }
        }

        return response()->json([
            'success' => true,
            'job' => [
                'id' => $job->id,
                'title' => $job->title,
                'contact' => $job->contact?->name,
            ],
            'results' => $results,
        ]);
    }

    /**
     * @return array<int, array{label: string, token: string, description: string}>
     */
    private function templateVariables(): array
    {
        return [
            ['label' => 'Contacto: Nombre', 'token' => '{{contact.name}}', 'description' => 'Nombre del cliente asociado al trabajo.'],
            ['label' => 'Contacto: Número', 'token' => '{{contact.number}}', 'description' => 'Alias: mobile/phone/primary/secondary (el primero disponible).'],
            ['label' => 'Contacto: Email', 'token' => '{{contact.email}}', 'description' => 'Correo del contacto.'],
            ['label' => 'Factura: Número', 'token' => '{{invoice.number}}', 'description' => 'Alias de document_number.'],
            ['label' => 'Factura: Total', 'token' => '{{invoice.total_amount}}', 'description' => 'Total de la factura.'],
            ['label' => 'Trabajo: ID', 'token' => '{{job.id}}', 'description' => 'UUID del workflow job.'],
            ['label' => 'Trabajo: Prioridad', 'token' => '{{job.priority}}', 'description' => 'Prioridad del trabajo.'],
            ['label' => 'Trabajo: Fecha vencimiento', 'token' => '{{job.due_date}}', 'description' => 'ISO timestamp si existe.'],
            ['label' => 'Etapa (entrada): Nombre', 'token' => '{{to_stage.name}}', 'description' => 'Nombre de la etapa a la que entró.'],
            ['label' => 'Etapa (salida): Nombre', 'token' => '{{from_stage.name}}', 'description' => 'Nombre de la etapa anterior (si aplica).'],
            ['label' => 'Actor: Nombre', 'token' => '{{actor.name}}', 'description' => 'Usuario que movió la tarea (si aplica).'],
        ];
    }

    /**
     * Build definition from visual builder data (nodes with positions + edges).
     *
     * @param  array<string, mixed>  $validated
     * @return array{nodes: array<int, array<string, mixed>>, edges: array<int, array{from:string, to:string, sourceHandle?:string|null, targetHandle?:string|null}>}
     */
    private function buildDefinitionFromVisualBuilder(array $validated): array
    {
        $nodes = [];
        $edges = [];

        // If 'nodes' array is provided from visual builder, use it directly
        if (isset($validated['nodes']) && is_array($validated['nodes'])) {
            foreach ($validated['nodes'] as $node) {
                $nodes[] = [
                    'id' => $node['id'] ?? uniqid('node-'),
                    'type' => $node['type'] ?? 'http.webhook',
                    'position' => $node['position'] ?? ['x' => 100, 'y' => 200],
                    'config' => $node['config'] ?? [],
                ];
            }
        }

        // If 'edges' array is provided, use it directly
        if (isset($validated['edges']) && is_array($validated['edges'])) {
            foreach ($validated['edges'] as $edge) {
                $edges[] = [
                    'from' => $edge['from'] ?? '',
                    'to' => $edge['to'] ?? '',
                    'sourceHandle' => $edge['sourceHandle'] ?? null,
                    'targetHandle' => $edge['targetHandle'] ?? null,
                ];
            }
        }

        // Fallback: if no nodes provided, build from actions (backward compatibility)
        if ($nodes === [] && isset($validated['actions']) && is_array($validated['actions'])) {
            $stageId = $validated['trigger_stage_id'] ?? '';

            $nodes[] = [
                'id' => 't1',
                'type' => 'workflow.stage_entered',
                'position' => ['x' => 100, 'y' => 200],
                'config' => ['stage_id' => $stageId],
            ];

            $previous = 't1';
            foreach ($validated['actions'] as $index => $action) {
                $nodeId = 'a' . ($index + 1);

                $nodes[] = [
                    'id' => $nodeId,
                    'type' => $action['type'] ?? 'http.webhook',
                    'position' => ['x' => 100 + (($index + 1) * 250), 'y' => 200],
                    'config' => $action['config'] ?? [],
                ];

                $edges[] = ['from' => $previous, 'to' => $nodeId];
                $previous = $nodeId;
            }
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
        ];
    }
}
