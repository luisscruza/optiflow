<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\WorkflowJob;
use App\Models\WorkflowStage;
use Illuminate\Support\Facades\DB;

final readonly class CreateWorkflowJobAction
{
    public function __construct(
        private RecordWorkflowEventAction $recordEvent,
    ) {}

    /**
     * Execute the action.
     *
     * @param  array{invoice_id?: int, contact_id?: int, prescription_id?: int, priority?: string, due_date?: string, started_at?: string, completed_at?: string, canceled_at?: string, notes?: string, metadata?: array<string, mixed>}  $data
     */
    public function handle(WorkflowStage $stage, array $data): WorkflowJob
    {
        return DB::transaction(function () use ($stage, $data): WorkflowJob {
            $job = $stage->jobs()->create([
                'workflow_id' => $stage->workflow->id,
                'invoice_id' => $data['invoice_id'] ?? null,
                'contact_id' => $data['contact_id'] ?? null,
                'prescription_id' => $data['prescription_id'] ?? null,
                'priority' => $data['priority'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'started_at' => $data['started_at'] ?? null,
                'completed_at' => $data['completed_at'] ?? null,
                'canceled_at' => $data['canceled_at'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            // Record the initial stage assignment event
            $this->recordEvent->stageChanged($job, null, $stage);

            if (isset($data['notes'])) {
                $job->comment($data['notes']);
                $this->recordEvent->noteAdded($job);
            }

            return $job;
        });
    }
}
