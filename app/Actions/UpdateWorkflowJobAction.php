<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\WorkflowJob;
use Illuminate\Support\Facades\DB;

final readonly class UpdateWorkflowJobAction
{
    /**
     * Execute the action.
     *
     * @param  array{invoice_id?: int, contact_id?: int, priority?: string, due_date?: string, started_at?: string, completed_at?: string, canceled_at?: string}  $data
     */
    public function handle(WorkflowJob $job, array $data): WorkflowJob
    {
        return DB::transaction(function () use ($job, $data): WorkflowJob {
            $job->update([
                'invoice_id' => $data['invoice_id'] ?? $job->invoice_id,
                'contact_id' => $data['contact_id'] ?? $job->contact_id,
                'priority' => $data['priority'] ?? $job->priority,
                'due_date' => $data['due_date'] ?? $job->due_date,
                'started_at' => $data['started_at'] ?? $job->started_at,
                'completed_at' => $data['completed_at'] ?? $job->completed_at,
                'canceled_at' => $data['canceled_at'] ?? $job->canceled_at,
            ]);

            return $job->fresh();
        });
    }
}
