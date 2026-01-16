<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AutomationNodeRun;
use App\Models\AutomationRun;
use App\Models\Invoice;
use App\Models\WorkflowJob;
use App\Models\WorkflowStage;
use App\Services\Automation\NodeRunners\NodeRunnerRegistry;
use App\Services\Automation\Support\AutomationContext;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class ExecuteAutomationNodeJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $input
     */
    public function __construct(
        public string $automationRunId,
        public string $nodeId,
        public array $input,
    ) {}

    public function handle(NodeRunnerRegistry $registry): void
    {
        DB::transaction(function () use ($registry): void {
            /** @var AutomationRun $run */
            $run = AutomationRun::query()->lockForUpdate()->findOrFail($this->automationRunId);

            // Idempotency: if this node already ran successfully, don't run it again.
            $existing = AutomationNodeRun::query()
                ->where('automation_run_id', $run->id)
                ->where('node_id', $this->nodeId)
                ->first();

            if ($existing instanceof AutomationNodeRun && $existing->status === 'success') {
                return;
            }

            $version = $run->version;
            $definition = is_array($version?->definition) ? $version->definition : [];

            /** @var array<int, mixed> $nodes */
            $nodes = Arr::get($definition, 'nodes', []);
            /** @var array<int, mixed> $edges */
            $edges = Arr::get($definition, 'edges', []);

            $node = $this->findNode($nodes, $this->nodeId);
            if ($node === null) {
                $this->markRunFailed($run, "Node [{$this->nodeId}] not found.");

                return;
            }

            $type = $node['type'] ?? null;
            $config = $node['config'] ?? [];

            if (! is_string($type) || ! is_array($config) || ! $registry->has($type)) {
                $this->markRunFailed($run, "Unsupported node type [{$type}].");

                return;
            }

            $context = $this->buildContextForRun($run);
            if (! $context instanceof AutomationContext) {
                $this->markRunFailed($run, "Unsupported subject type [{$run->subject_type}].");

                return;
            }

            $nodeRun = $existing ?? new AutomationNodeRun();
            $nodeRun->automation_run_id = $run->id;
            $nodeRun->node_id = $this->nodeId;
            $nodeRun->node_type = $type;
            $nodeRun->status = 'running';
            $nodeRun->attempts = (int) ($nodeRun->attempts ?? 0) + 1;
            $nodeRun->input = $this->input;
            $nodeRun->started_at = now();
            $nodeRun->save();

            try {
                $result = $registry->get($type)->run($context, $config, $this->input);

                $nodeRun->status = $result->success ? 'success' : 'failed';
                $nodeRun->output = $result->output;
                $nodeRun->finished_at = now();
                $nodeRun->save();

                // For condition nodes, determine which branch to follow
                $branch = null;
                if ($type === 'logic.condition' && $result->success) {
                    $branch = $result->output['branch'] ?? 'true';
                }

                $nextNodes = $this->nextNodeIds($edges, $this->nodeId, $branch);

                // Update pending count: one finished, plus newly scheduled.
                $run->pending_nodes = max(0, (int) $run->pending_nodes - 1);
                $run->pending_nodes += count($nextNodes);

                if (! $result->success) {
                    $run->status = 'failed';
                    $run->error = 'Node failed';
                }

                if ($run->pending_nodes === 0 && $run->status !== 'failed') {
                    $run->status = 'completed';
                    $run->finished_at = now();
                }

                $run->save();

                foreach ($nextNodes as $nextNodeId) {
                    dispatch(new self(
                        automationRunId: $run->id,
                        nodeId: $nextNodeId,
                        input: array_merge($this->input, [
                            'last_node' => [
                                'id' => $this->nodeId,
                                'type' => $type,
                                'output' => $result->output,
                            ],
                        ]),
                    ));
                }
            } catch (Exception $e) {
                $nodeRun->status = 'failed';
                $nodeRun->error = $e->getMessage();
                $nodeRun->finished_at = now();
                $nodeRun->save();

                $this->markRunFailed($run, $e->getMessage());
            }
        });
    }

    private function buildContextForRun(AutomationRun $run): ?AutomationContext
    {
        if ($run->subject_type === 'workflow_job') {
            /** @var WorkflowJob $job */
            $job = WorkflowJob::query()
                ->withoutWorkspaceScope()
                ->with(['contact', 'invoice'])
                ->findOrFail($run->subject_id);

            return new AutomationContext(
                job: $job,
                fromStage: null,
                toStage: WorkflowStage::query()->find($job->workflow_stage_id),
                actor: Auth::user(),
                invoice: $job->invoice,
                contact: $job->contact,
            );
        }

        if ($run->subject_type === 'invoice') {
            /** @var Invoice $invoice */
            $invoice = Invoice::query()
                ->withoutWorkspaceScope()
                ->with(['contact'])
                ->findOrFail($run->subject_id);

            return new AutomationContext(
                job: null,
                fromStage: null,
                toStage: null,
                actor: Auth::user(),
                invoice: $invoice,
                contact: $invoice->contact,
            );
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $nodes
     * @return array<string, mixed>|null
     */
    private function findNode(array $nodes, string $nodeId): ?array
    {
        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            if (($node['id'] ?? null) === $nodeId) {
                return $node;
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $edges
     * @param  string|null  $branch  If provided (for condition nodes), filter by sourceHandle
     * @return array<int, string>
     */
    private function nextNodeIds(array $edges, string $fromNodeId, ?string $branch = null): array
    {
        $next = [];

        foreach ($edges as $edge) {
            if (! is_array($edge)) {
                continue;
            }

            $from = $edge['from'] ?? null;
            $to = $edge['to'] ?? null;

            if ($from !== $fromNodeId || ! is_string($to) || $to === '') {
                continue;
            }

            // If a branch is specified (condition node), filter by sourceHandle
            if ($branch !== null) {
                $sourceHandle = $edge['sourceHandle'] ?? null;
                // Only follow the edge if the sourceHandle matches the branch
                if ($sourceHandle !== $branch) {
                    continue;
                }
            }

            $next[] = $to;
        }

        return array_values(array_unique($next));
    }

    private function markRunFailed(AutomationRun $run, string $error): void
    {
        $run->pending_nodes = max(0, (int) $run->pending_nodes - 1);
        $run->status = 'failed';
        $run->error = $error;

        if ($run->pending_nodes === 0) {
            $run->finished_at = now();
        }

        $run->save();
    }
}
