<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\WorkflowJob;
use Illuminate\Support\Facades\DB;

final readonly class UpdateWorkflowJobAction
{
    public function __construct(
        private RecordWorkflowEventAction $recordEvent,
    ) {}

    /**
     * Execute the action.
     *
     * @param  array{invoice_id?: int, contact_id?: int, priority?: string|null, due_date?: string|null, started_at?: string|null, completed_at?: string|null, canceled_at?: string|null}  $data
     */
    public function handle(WorkflowJob $job, array $data): WorkflowJob
    {
        return DB::transaction(function () use ($job, $data): WorkflowJob {
            $oldPriority = $job->priority;

            $job->update([
                'invoice_id' => $data['invoice_id'] ?? $job->invoice_id,
                'contact_id' => $data['contact_id'] ?? $job->contact_id,
                'priority' => array_key_exists('priority', $data) ? $data['priority'] : $job->priority,
                'due_date' => array_key_exists('due_date', $data) ? $data['due_date'] : $job->due_date,
                'started_at' => array_key_exists('started_at', $data) ? $data['started_at'] : $job->started_at,
                'completed_at' => array_key_exists('completed_at', $data) ? $data['completed_at'] : $job->completed_at,
                'canceled_at' => array_key_exists('canceled_at', $data) ? $data['canceled_at'] : $job->canceled_at,
            ]);

            // Record priority change event if priority was updated
            if (array_key_exists('priority', $data) && $oldPriority !== $data['priority']) {
                $this->recordEvent->priorityUpdated($job, $oldPriority, $data['priority']);
            }

            return $job->fresh();
        });
    }
}
