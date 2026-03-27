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
use App\Services\Automation\Support\AutomationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class AutomationController
{
    public function index(): Response
    {
        $user = Auth::user();

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

        $cloneableWorkspaces = $user?->workspaces()
            ->where('workspaces.id', '!=', $user->current_workspace_id)
            ->orderBy('workspaces.name')
            ->get(['workspaces.id', 'workspaces.name'])
            ->map(fn ($workspace): array => [
                'id' => $workspace->id,
                'name' => $workspace->name,
            ])
            ->values()
            ->all() ?? [];

        return Inertia::render('automations/index', [
            'automations' => $automations,
            'cloneableWorkspaces' => $cloneableWorkspaces,
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

        $templateTokens = app(AutomationContext::class)->toTemplateData();

        $variables = [];

        foreach ($templateTokens as $key => $value) {

            if ($key === 'input') {
                continue;
            }

            $variables[] = [
                'label' => str_replace('_', ' ', ucfirst($key)),
                'token' => '{{'.$key.'}}',
                'description' => 'Valor de '.$key.' en el contexto de la automatización.',
            ];

            // If the value is an array, we can also provide tokens for its keys
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $variables[] = [
                        'label' => str_replace('_', ' ', ucfirst($subKey)).' ('.$key.')',
                        'token' => '{{'.$key.'.'.$subKey.'}}',
                        'description' => 'Valor de '.$subKey.' dentro de '.$key.' en el contexto de la automatización.',
                    ];

                    // If the subValue is also an array, we can go one level deeper (e.g. for contact.address.street)
                    if (is_array($subValue)) {
                        foreach ($subValue as $subSubKey => $subSubValue) {
                            $variables[] = [
                                'label' => str_replace('_', ' ', ucfirst($subSubKey)).' ('.$subKey.' dentro de '.$key.')',
                                'token' => '{{'.$key.'.'.$subKey.'.'.$subSubKey.'}}',
                                'description' => 'Valor de '.$subSubKey.' dentro de '.$subKey.' dentro de '.$key.' en el contexto de la automatización.',
                            ];
                        }
                    }
                }
            }

        }

        return $variables;
    }
}
