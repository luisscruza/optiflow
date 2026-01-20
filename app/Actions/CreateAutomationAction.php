<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Automation;
use App\Models\AutomationTrigger;
use App\Models\AutomationVersion;
use App\Models\User;
use App\Services\Automation\NodeTypes\NodeTypeRegistry;
use Illuminate\Support\Facades\DB;

final readonly class CreateAutomationAction
{
    public function __construct(private NodeTypeRegistry $nodeTypeRegistry)
    {
        //
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): Automation
    {
        return DB::transaction(function () use ($user, $data): Automation {
            $automation = Automation::query()->create([
                'workspace_id' => $user->current_workspace_id,
                'name' => $data['name'],
                'is_active' => $data['is_active'],
                'published_version' => 1,
            ]);

            $definition = $this->buildDefinitionFromVisualBuilder($data);

            $version = AutomationVersion::query()->create([
                'automation_id' => $automation->id,
                'version' => 1,
                'definition' => $definition,
                'created_by' => $user->id,
            ]);

            $triggerData = $this->resolveTriggerData($definition, $data);

            AutomationTrigger::query()->create([
                'automation_id' => $automation->id,
                'workspace_id' => $user->current_workspace_id,
                'event_key' => $triggerData['event_key'],
                'workflow_id' => $triggerData['workflow_id'],
                'workflow_stage_id' => $triggerData['workflow_stage_id'],
                'is_active' => true,
            ]);

            $automation->published_version = $version->version;
            $automation->save();

            return $automation;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{nodes: array<int, array<string, mixed>>, edges: array<int, array{from:string, to:string, sourceHandle?:string|null, targetHandle?:string|null}>}
     */
    private function buildDefinitionFromVisualBuilder(array $validated): array
    {
        $nodes = [];
        $edges = [];

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
                $nodeId = 'a'.($index + 1);

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

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $validated
     * @return array{event_key: string, workflow_id: int|null, workflow_stage_id: int|null}
     */
    private function resolveTriggerData(array $definition, array $validated): array
    {
        $triggerType = $validated['trigger_type'];

        $triggerNode = collect($definition['nodes'] ?? [])->first(function ($node): bool {
            $type = $node['type'] ?? null;

            return is_array($node) && is_string($type) && $this->nodeTypeRegistry->get($type)?->category === 'trigger';
        });

        $triggerNodeType = is_array($triggerNode) && is_string($triggerNode['type'] ?? null)
            ? (string) $triggerNode['type']
            : $triggerType;

        $triggerConfig = is_array($triggerNode) ? ($triggerNode['config'] ?? []) : [];
        if (! is_array($triggerConfig)) {
            $triggerConfig = [];
        }

        $eventKey = $this->nodeTypeRegistry->getEventKeyForTrigger($triggerNodeType) ?? 'workflow.job.stage_changed';

        $workflowId = null;
        $stageId = null;
        if ($triggerNodeType === 'workflow.stage_entered') {
            $workflowId = $triggerConfig['workflow_id'] ?? ($validated['trigger_workflow_id'] ?? null);
            $stageId = $triggerConfig['stage_id'] ?? ($validated['trigger_stage_id'] ?? null);
        }

        return [
            'event_key' => $eventKey,
            'workflow_id' => $workflowId,
            'workflow_stage_id' => $stageId,
        ];
    }
}
