<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionNotFoundException;
use App\Models\Automation;
use App\Models\AutomationVersion;
use App\Models\User;
use App\Models\WorkflowJob;
use App\Services\Automation\NodeRunners\NodeRunnerRegistry;
use App\Services\Automation\Support\AutomationContext;
use Illuminate\Support\Facades\Auth;
use Throwable;

final class RunAutomationTestAction
{
    /**
     * @param  array{job_id: string, dry_run?: bool}  $data
     * @return array{success: bool, job: array{id: string, title: string, contact: string|null}, results: array<int, array<string, mixed>>}
     */
    public function handle(Automation $automation, array $data, NodeRunnerRegistry $registry, ?User $actor = null): array
    {
        $job = WorkflowJob::query()
            ->with(['contact', 'invoice', 'workflowStage', 'workflow'])
            ->findOrFail($data['job_id']);

        $version = AutomationVersion::query()
            ->where('automation_id', $automation->id)
            ->orderByDesc('version')
            ->first();

        if (! $version) {
            throw new ActionNotFoundException('No hay versión publicada');
        }

        $definition = $version->definition;
        $nodes = $definition['nodes'] ?? [];
        $edges = $definition['edges'] ?? [];

        $stage = $job->workflowStage;
        $context = new AutomationContext(
            job: $job,
            fromStage: $stage,
            toStage: $stage,
            actor: $actor ?? Auth::user(),
        );

        $results = [];
        $dryRun = $data['dry_run'] ?? true;

        $adjacency = [];
        foreach ($edges as $edge) {
            $from = $edge['from'] ?? '';
            $to = $edge['to'] ?? '';
            if ($from && $to) {
                $adjacency[$from][] = $to;
            }
        }

        $nodeIndex = [];
        foreach ($nodes as $node) {
            $nodeIndex[$node['id']] = $node;
        }

        $currentNodes = array_filter($nodes, fn ($node) => ($node['type'] ?? null) === 'workflow.stage_entered');
        $queue = array_map(fn ($node) => $node['id'], $currentNodes);

        $visited = [];
        $input = [];

        while ($queue !== []) {
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

            if ($nodeType === 'workflow.stage_entered') {
                $result['status'] = 'success';
                $result['output'] = ['message' => 'Trigger activado'];
                $results[] = $result;

                foreach ($adjacency[$nodeId] ?? [] as $nextId) {
                    $queue[] = $nextId;
                }

                continue;
            }

            if (! $registry->has($nodeType)) {
                $result['status'] = 'error';
                $result['output'] = ['error' => "Runner no encontrado para: {$nodeType}"];
                $results[] = $result;

                continue;
            }

            try {
                if ($dryRun) {
                    $templateData = $context->toTemplateData($input);
                    $result['status'] = 'dry_run';
                    $result['output'] = [
                        'message' => 'Se ejecutaría este nodo',
                        'config' => $config,
                        'available_data' => array_keys($templateData),
                    ];
                } else {
                    $runner = $registry->get($nodeType);
                    $nodeResult = $runner->run($context, $config, $input);

                    $result['status'] = $nodeResult->success ? 'success' : 'error';
                    $result['output'] = $nodeResult->output;

                    if ($nodeType === 'logic.condition' && $nodeResult->success) {
                        $branch = $nodeResult->output['branch'] ?? 'true';
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

                    $input = array_merge($input, $nodeResult->output);
                }
            } catch (Throwable $exception) {
                $result['status'] = 'error';
                $result['output'] = ['error' => $exception->getMessage()];
            }

            $results[] = $result;

            if ($nodeType !== 'logic.condition') {
                foreach ($adjacency[$nodeId] ?? [] as $nextId) {
                    $queue[] = $nextId;
                }
            }
        }

        return [
            'success' => true,
            'job' => [
                'id' => $job->id,
                'title' => $job->contact?->name ?? $job->workflow?->name ?? (string) $job->id,
                'contact' => $job->contact?->name,
            ],
            'results' => $results,
        ];
    }
}
