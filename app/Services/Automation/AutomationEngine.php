<?php

declare(strict_types=1);

namespace App\Services\Automation;

use App\Jobs\ExecuteAutomationNodeJob;
use App\Models\AutomationRun;
use App\Models\AutomationTrigger;
use App\Models\AutomationVersion;
use App\Models\Invoice;
use App\Models\User;
use App\Models\WorkflowJob;
use App\Models\WorkflowStage;
use App\Services\Automation\Support\AutomationContext;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;

final class AutomationEngine
{
    /**
     * Payload keys expected from the domain event.
     *
     * @param  array{workflow_job_id:string, from_stage_id?:string|null, to_stage_id:string, workflow_id:string, workspace_id?:int|null, user_id?:int|null}  $payload
     */
    public function handleWorkflowJobStageChanged(array $payload): void
    {
        $jobId = $payload['workflow_job_id'] ?? null;
        $toStageId = $payload['to_stage_id'] ?? null;

        if (! is_string($jobId) || ! is_string($toStageId)) {
            throw new InvalidArgumentException('Invalid payload for workflow.job.stage_changed');
        }

        /** @var WorkflowJob $job */
        $job = WorkflowJob::query()->withoutWorkspaceScope()->findOrFail($jobId);

        $workspaceId = $payload['workspace_id'] ?? $job->workspace_id;
        if (! is_int($workspaceId)) {
            return;
        }

        $triggers = AutomationTrigger::query()
            ->withoutWorkspaceScope()
            ->where('workspace_id', $workspaceId)
            ->where('event_key', 'workflow.job.stage_changed')
            ->where('workflow_stage_id', $toStageId)
            ->where('is_active', true)
            ->get();

        foreach ($triggers as $trigger) {
            $this->startAutomationRunForTrigger($trigger->automation_id, $job, $payload);
        }
    }

    /**
     * Payload keys expected from invoice events.
     *
     * @param  array{invoice_id:int|string, workspace_id?:int|null, user_id?:int|null}  $payload
     */
    public function handleInvoiceCreated(array $payload): void
    {
        $invoiceId = $payload['invoice_id'] ?? null;

        if (! is_int($invoiceId) && ! is_string($invoiceId)) {
            throw new InvalidArgumentException('Invalid payload for invoice.created');
        }

        /** @var Invoice $invoice */
        $invoice = Invoice::query()
            ->withoutWorkspaceScope()
            ->with(['contact'])
            ->findOrFail((string) $invoiceId);

        $workspaceId = $payload['workspace_id'] ?? $invoice->workspace_id;
        if (! is_int($workspaceId)) {
            return;
        }

        $triggers = AutomationTrigger::query()
            ->withoutWorkspaceScope()
            ->where('workspace_id', $workspaceId)
            ->where('event_key', 'invoice.created')
            ->where('is_active', true)
            ->get();

        foreach ($triggers as $trigger) {
            $this->startAutomationRunForInvoiceTrigger($trigger->automation_id, 'invoice.created', $invoice, $payload);
        }
    }

    /**
     * @param  array{invoice_id:int|string, workspace_id?:int|null, user_id?:int|null}  $payload
     */
    public function handleInvoiceUpdated(array $payload): void
    {
        $invoiceId = $payload['invoice_id'] ?? null;

        if (! is_int($invoiceId) && ! is_string($invoiceId)) {
            throw new InvalidArgumentException('Invalid payload for invoice.updated');
        }

        /** @var Invoice $invoice */
        $invoice = Invoice::query()
            ->withoutWorkspaceScope()
            ->with(['contact'])
            ->findOrFail((string) $invoiceId);

        $workspaceId = $payload['workspace_id'] ?? $invoice->workspace_id;
        if (! is_int($workspaceId)) {
            return;
        }

        $triggers = AutomationTrigger::query()
            ->withoutWorkspaceScope()
            ->where('workspace_id', $workspaceId)
            ->where('event_key', 'invoice.updated')
            ->where('is_active', true)
            ->get();

        foreach ($triggers as $trigger) {
            $this->startAutomationRunForInvoiceTrigger($trigger->automation_id, 'invoice.updated', $invoice, $payload);
        }
    }

    /**
     * @param  array{workflow_job_id:string, from_stage_id?:string|null, to_stage_id:string, workflow_id:string, workspace_id?:int|null, user_id?:int|null}  $payload
     */
    private function startAutomationRunForTrigger(string $automationId, WorkflowJob $job, array $payload): void
    {
        DB::transaction(function () use ($automationId, $job, $payload): void {
            $version = AutomationVersion::query()
                ->where('automation_id', $automationId)
                ->orderByDesc('version')
                ->first();

            if (! $version instanceof AutomationVersion) {
                return;
            }

            $definition = is_array($version->definition) ? $version->definition : [];
            $nodes = Arr::get($definition, 'nodes', []);
            $edges = Arr::get($definition, 'edges', []);

            if (! is_array($nodes) || ! is_array($edges)) {
                return;
            }

            $triggerNodeIds = $this->findMatchingTriggerNodesForEvent('workflow.job.stage_changed', $nodes, $payload);
            if ($triggerNodeIds === []) {
                return;
            }

            $run = AutomationRun::query()->create([
                'automation_id' => $automationId,
                'automation_version_id' => $version->id,
                'workspace_id' => $job->workspace_id,
                'trigger_event_key' => 'workflow.job.stage_changed',
                'subject_type' => 'workflow_job',
                'subject_id' => $job->id,
                'status' => 'running',
                'pending_nodes' => 0,
                'started_at' => now(),
            ]);

            $actor = null;
            if (isset($payload['user_id']) && is_int($payload['user_id'])) {
                $actor = User::query()->find($payload['user_id']);
            } elseif (Auth::check()) {
                $actor = Auth::user();
            }

            $fromStage = isset($payload['from_stage_id']) && is_string($payload['from_stage_id'])
                ? WorkflowStage::query()->find($payload['from_stage_id'])
                : null;

            $toStage = WorkflowStage::query()->find($payload['to_stage_id']);

            $job->loadMissing(['contact', 'invoice']);

            $context = new AutomationContext(
                job: $job,
                fromStage: $fromStage,
                toStage: $toStage,
                actor: $actor,
                invoice: $job->invoice,
                contact: $job->contact,
            );

            $startingNodeIds = $this->nextNodeIdsFromTriggers($edges, $triggerNodeIds);
            if ($startingNodeIds === []) {
                $run->status = 'completed';
                $run->finished_at = now();
                $run->save();

                return;
            }

            $run->pending_nodes = count($startingNodeIds);
            $run->save();

            foreach ($startingNodeIds as $nodeId) {
                Queue::push(new ExecuteAutomationNodeJob(
                    automationRunId: $run->id,
                    nodeId: $nodeId,
                    input: $context->toTemplateData(),
                ));
            }
        });
    }

    /**
     * @param  array{invoice_id:int|string, workspace_id?:int|null, user_id?:int|null}  $payload
     */
    private function startAutomationRunForInvoiceTrigger(string $automationId, string $eventKey, Invoice $invoice, array $payload): void
    {
        DB::transaction(function () use ($automationId, $eventKey, $invoice, $payload): void {
            $version = AutomationVersion::query()
                ->where('automation_id', $automationId)
                ->orderByDesc('version')
                ->first();

            if (! $version instanceof AutomationVersion) {
                return;
            }

            $definition = is_array($version->definition) ? $version->definition : [];
            $nodes = Arr::get($definition, 'nodes', []);
            $edges = Arr::get($definition, 'edges', []);

            if (! is_array($nodes) || ! is_array($edges)) {
                return;
            }

            $triggerNodeIds = $this->findMatchingTriggerNodesForEvent($eventKey, $nodes, $payload);
            if ($triggerNodeIds === []) {
                return;
            }

            $run = AutomationRun::query()->create([
                'automation_id' => $automationId,
                'automation_version_id' => $version->id,
                'workspace_id' => $invoice->workspace_id,
                'trigger_event_key' => $eventKey,
                'subject_type' => 'invoice',
                'subject_id' => (string) $invoice->id,
                'status' => 'running',
                'pending_nodes' => 0,
                'started_at' => now(),
            ]);

            $actor = null;
            if (isset($payload['user_id']) && is_int($payload['user_id'])) {
                $actor = User::query()->find($payload['user_id']);
            } elseif (Auth::check()) {
                $actor = Auth::user();
            }

            $context = new AutomationContext(
                job: null,
                fromStage: null,
                toStage: null,
                actor: $actor,
                invoice: $invoice,
                contact: $invoice->contact,
            );

            $startingNodeIds = $this->nextNodeIdsFromTriggers($edges, $triggerNodeIds);
            if ($startingNodeIds === []) {
                $run->status = 'completed';
                $run->finished_at = now();
                $run->save();

                return;
            }

            $run->pending_nodes = count($startingNodeIds);
            $run->save();

            foreach ($startingNodeIds as $nodeId) {
                Queue::push(new ExecuteAutomationNodeJob(
                    automationRunId: $run->id,
                    nodeId: $nodeId,
                    input: $context->toTemplateData(),
                ));
            }
        });
    }

    /**
     * @param  array<int, mixed>  $edges
     * @param  array<int, string>  $fromNodeIds
     * @return array<int, string>
     */
    private function nextNodeIdsFromTriggers(array $edges, array $fromNodeIds): array
    {
        $next = [];

        foreach ($edges as $edge) {
            if (! is_array($edge)) {
                continue;
            }

            $from = $edge['from'] ?? null;
            $to = $edge['to'] ?? null;

            if (! is_string($from) || ! is_string($to)) {
                continue;
            }

            if (! in_array($from, $fromNodeIds, true)) {
                continue;
            }

            $next[] = $to;
        }

        return array_values(array_unique($next));
    }

    /**
     * @param  array<int, mixed>  $nodes
     * @return array<int, string>
     */
    private function findMatchingTriggerNodesForEvent(string $eventKey, array $nodes, array $payload): array
    {
        $matches = [];

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            $type = $node['type'] ?? null;
            if (! is_string($type) || $type === '') {
                continue;
            }

            if ($eventKey === 'invoice.created' && $type !== 'invoice.created') {
                continue;
            }

            if ($eventKey === 'invoice.updated' && $type !== 'invoice.updated') {
                continue;
            }

            if ($eventKey === 'workflow.job.stage_changed') {
                if ($type !== 'workflow.stage_entered') {
                    continue;
                }

                $toStageId = $payload['to_stage_id'] ?? null;
                if (! is_string($toStageId) || $toStageId === '') {
                    continue;
                }

                $config = $node['config'] ?? [];
                if (! is_array($config)) {
                    continue;
                }

                if (($config['stage_id'] ?? null) !== $toStageId) {
                    continue;
                }
            }

            $id = $node['id'] ?? null;
            if (is_string($id) && $id !== '') {
                $matches[] = $id;
            }
        }

        return $matches;
    }
}
