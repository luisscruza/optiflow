<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Automation;
use App\Models\AutomationRun;
use Inertia\Inertia;
use Inertia\Response;

final class AutomationRunController extends Controller
{
    public function index(Automation $automation): Response
    {
        $runs = AutomationRun::query()
            ->where('automation_id', $automation->id)
            ->with(['version'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return Inertia::render('automations/runs/index', [
            'automation' => [
                'id' => $automation->id,
                'name' => $automation->name,
                'is_active' => (bool) $automation->is_active,
            ],
            'runs' => $runs->through(fn (AutomationRun $run): array => [
                'id' => $run->id,
                'status' => $run->status,
                'trigger_event_key' => $run->trigger_event_key,
                'subject_type' => $run->subject_type,
                'subject_id' => $run->subject_id,
                'pending_nodes' => (int) $run->pending_nodes,
                'version_number' => $run->version?->version,
                'error' => $run->error,
                'started_at' => $run->started_at?->toISOString(),
                'finished_at' => $run->finished_at?->toISOString(),
                'created_at' => $run->created_at?->toISOString(),
            ]),
        ]);
    }

    public function show(Automation $automation, AutomationRun $run): Response
    {
        $run->load(['version', 'nodeRuns']);

        $definition = $run->version?->definition ?? [];
        $nodes = $definition['nodes'] ?? [];
        $edges = $definition['edges'] ?? [];

        // Build a map of node runs by node ID
        $nodeRunsMap = [];
        foreach ($run->nodeRuns as $nodeRun) {
            $nodeRunsMap[$nodeRun->node_id] = [
                'id' => $nodeRun->id,
                'node_id' => $nodeRun->node_id,
                'node_type' => $nodeRun->node_type,
                'status' => $nodeRun->status,
                'attempts' => $nodeRun->attempts,
                'input' => $nodeRun->input,
                'output' => $nodeRun->output,
                'error' => $nodeRun->error,
                'started_at' => $nodeRun->started_at?->toISOString(),
                'finished_at' => $nodeRun->finished_at?->toISOString(),
            ];
        }

        // Enrich nodes with their run status
        $enrichedNodes = array_map(function (array $node) use ($nodeRunsMap): array {
            $nodeId = $node['id'] ?? '';
            $nodeRun = $nodeRunsMap[$nodeId] ?? null;

            return [
                ...$node,
                'run' => $nodeRun,
            ];
        }, $nodes);

        return Inertia::render('automations/runs/show', [
            'automation' => [
                'id' => $automation->id,
                'name' => $automation->name,
            ],
            'run' => [
                'id' => $run->id,
                'status' => $run->status,
                'trigger_event_key' => $run->trigger_event_key,
                'subject_type' => $run->subject_type,
                'subject_id' => $run->subject_id,
                'pending_nodes' => (int) $run->pending_nodes,
                'version_number' => $run->version?->version,
                'error' => $run->error,
                'started_at' => $run->started_at?->toISOString(),
                'finished_at' => $run->finished_at?->toISOString(),
            ],
            'definition' => [
                'nodes' => $enrichedNodes,
                'edges' => $edges,
            ],
        ]);
    }
}
