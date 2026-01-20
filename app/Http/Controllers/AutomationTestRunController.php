<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Automation;
use App\Models\AutomationVersion;
use App\Models\WorkflowJob;
use App\Services\Automation\NodeRunners\NodeRunnerRegistry;
use App\Services\Automation\Support\AutomationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

final class AutomationTestRunController extends Controller
{
    /**
     * Run automation in test mode.
     */
    public function __invoke(Request $request, Automation $automation, NodeRunnerRegistry $registry): JsonResponse
    {
        $validated = $request->validate([
            'job_id' => ['required', 'uuid'],
            'dry_run' => ['sometimes', 'boolean'],
        ]);

        $job = WorkflowJob::query()
            ->with(['contact', 'invoice', 'workflowStage', 'workflow'])
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
        $stage = $job->workflowStage;
        $actor = Auth::user();

        $context = new AutomationContext(
            job: $job,
            fromStage: $stage,
            toStage: $stage,
            actor: $actor,
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
                    $result['output'] = $nodeResult->output;

                    // For condition nodes, determine which branch to follow
                    if ($nodeType === 'logic.condition' && $nodeResult->success) {
                        $branch = $nodeResult->output['branch'] ?? 'true';
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

                    $input = array_merge($input, $nodeResult->output);
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
                'title' => $job->contact?->name ?? $job->workflow?->name ?? (string) $job->id,
                'contact' => $job->contact?->name,
            ],
            'results' => $results,
        ]);
    }
}
