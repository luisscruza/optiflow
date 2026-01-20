<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateAutomationAction;
use App\Actions\UpdateAutomationAction;
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

final class AutomationController
{
    public function index(): Response
    {
        $automations = Automation::query()
            ->with(['triggers'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Automation $automation): array => [
                'id' => $automation->id,
                'name' => $automation->name,
                'is_active' => (bool) $automation->is_active,
                'published_version' => (int) $automation->published_version,
                'created_at' => optional($automation->created_at)?->toISOString(),
                'triggers' => $automation->triggers->map(fn (AutomationTrigger $trigger): array => [
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
                'stages' => fn ($query) => $query->orderBy('position'),
                'fields' => fn ($query) => $query->where('is_active', true)->orderBy('position'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Workflow $workflow): array => [
                'id' => $workflow->id,
                'name' => $workflow->name,
                'invoice_requirement' => $workflow->invoice_requirement,
                'stages' => $workflow->stages->map(fn (WorkflowStage $stage): array => [
                    'id' => $stage->id,
                    'name' => $stage->name,
                ])->all(),
                'fields' => $workflow->fields->map(fn (WorkflowField $field): array => [
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

    public function store(StoreAutomationRequest $request, CreateAutomationAction $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated());

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
                'stages' => fn ($query) => $query->orderBy('position'),
                'fields' => fn ($query) => $query->where('is_active', true)->orderBy('position'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Workflow $workflow): array => [
                'id' => $workflow->id,
                'name' => $workflow->name,
                'invoice_requirement' => $workflow->invoice_requirement,
                'stages' => $workflow->stages->map(fn (WorkflowStage $stage): array => [
                    'id' => $stage->id,
                    'name' => $stage->name,
                ])->all(),
                'fields' => $workflow->fields->map(fn (WorkflowField $field): array => [
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

    public function update(UpdateAutomationRequest $request, Automation $automation, UpdateAutomationAction $action): RedirectResponse
    {
        $action->handle($automation, $request->user(), $request->validated());

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
}
