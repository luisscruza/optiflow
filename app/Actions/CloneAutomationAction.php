<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Automation;
use App\Models\AutomationTrigger;
use App\Models\AutomationVersion;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class CloneAutomationAction
{
    public function handle(Automation $automation, User $user, Workspace $targetWorkspace): Automation
    {
        $sourceVersion = $automation->versions()
            ->where('version', $automation->published_version)
            ->first()
            ?? $automation->versions()->orderByDesc('version')->firstOrFail();

        return DB::transaction(function () use ($automation, $sourceVersion, $targetWorkspace, $user): Automation {
            $clonedAutomation = Automation::query()
                ->withoutGlobalScope('workspace')
                ->create([
                    'workspace_id' => $targetWorkspace->id,
                    'name' => $automation->name,
                    'is_active' => false,
                    'published_version' => 1,
                ]);

            AutomationVersion::query()->create([
                'automation_id' => $clonedAutomation->id,
                'version' => 1,
                'definition' => $this->sanitizeDefinition($sourceVersion->definition),
                'created_by' => $user->id,
            ]);

            $automation->triggers()
                ->withoutGlobalScope('workspace')
                ->get()
                ->each(function (AutomationTrigger $trigger) use ($clonedAutomation, $targetWorkspace): void {
                    AutomationTrigger::query()
                        ->withoutGlobalScope('workspace')
                        ->create([
                            'automation_id' => $clonedAutomation->id,
                            'workspace_id' => $targetWorkspace->id,
                            'event_key' => $trigger->event_key,
                            'workflow_id' => null,
                            'workflow_stage_id' => null,
                            'is_active' => false,
                        ]);
                });

            return $clonedAutomation;
        });
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    private function sanitizeDefinition(array $definition): array
    {
        $nodes = collect($definition['nodes'] ?? [])
            ->map(function (mixed $node): mixed {
                if (! is_array($node) || ($node['type'] ?? null) !== 'workflow.stage_entered') {
                    return $node;
                }

                $config = Arr::get($node, 'config', []);

                if (! is_array($config)) {
                    $config = [];
                }

                unset($config['workflow_id'], $config['stage_id']);

                $node['config'] = $config;

                return $node;
            })
            ->values()
            ->all();

        return [
            ...$definition,
            'nodes' => $nodes,
        ];
    }
}
