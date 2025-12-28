<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\WorkflowStage;
use Illuminate\Support\Facades\DB;

final readonly class CreateWorkflowJobAction
{
    /**
     * Execute the action.
     *
     * @param  array{invoice_id?: int, contact_id?: int, priority?: string, due_date?: string, started_at?: string, completed_at?: string, canceled_at?: string, notes?: string}  $data
     */
    public function handle(): void
    {
        DB::transaction(function (WorkflowStage $stage, array $data): void {
            $job = $stage->jobs()->create([
                'workflow_id' => $stage->workflow->id,
                'invoice_id' => $data['invoice_id'] ?? null,
                'contact_id' => $data['contact_id'] ?? null,
                'priority' => $data['priority'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'started_at' => $data['started_at'] ?? null,
                'completed_at' => $data['completed_at'] ?? null,
                'canceled_at' => $data['canceled_at'] ?? null,
            ]);

            if (isset($data['notes'])) {
                $job->comment($data['notes']);
            }
        });
    }
}
