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
use App\Models\WorkflowField;
use App\Models\WorkflowStage;
use App\Services\Automation\NodeTypes\NodeTypeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

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
            ->with([
                'stages' => fn($query) => $query->orderBy('position'),
                'fields' => fn($query) => $query->where('is_active', true)->orderBy('position'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn(Workflow $workflow): array => [
                'id' => $workflow->id,
                'name' => $workflow->name,
                'invoice_requirement' => $workflow->invoice_requirement,
                'stages' => $workflow->stages->map(fn(WorkflowStage $stage): array => [
                    'id' => $stage->id,
                    'name' => $stage->name,
                ])->all(),
                'fields' => $workflow->fields->map(fn(WorkflowField $field): array => [
                    'id' => $field->id,
                    'name' => $field->name,
                    'key' => $field->key,
                    'type' => $field->type->value,
                    'is_required' => (bool) $field->is_required,
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
            ->with([
                'stages' => fn($query) => $query->orderBy('position'),
                'fields' => fn($query) => $query->where('is_active', true)->orderBy('position'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn(Workflow $workflow): array => [
                'id' => $workflow->id,
                'name' => $workflow->name,
                'invoice_requirement' => $workflow->invoice_requirement,
                'stages' => $workflow->stages->map(fn(WorkflowStage $stage): array => [
                    'id' => $stage->id,
                    'name' => $stage->name,
                ])->all(),
                'fields' => $workflow->fields->map(fn(WorkflowField $field): array => [
                    'id' => $field->id,
                    'name' => $field->name,
                    'key' => $field->key,
                    'type' => $field->type->value,
                    'is_required' => (bool) $field->is_required,
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
