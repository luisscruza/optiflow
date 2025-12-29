<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\WorkflowJob;
use App\Models\WorkflowStage;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class MoveWorkflowJobAction
{
    public function __construct(
        private RecordWorkflowEventAction $recordEvent,
    ) {}

    /**
     * Move a job to a different stage within the same workflow.
     */
    public function handle(WorkflowJob $job, WorkflowStage $targetStage): WorkflowJob
    {
        return DB::transaction(function () use ($job, $targetStage): WorkflowJob {
            if ($job->workflow_id !== $targetStage->workflow_id) {
                throw new InvalidArgumentException('Target stage must belong to the same workflow.');
            }

            $fromStage = $job->workflowStage;

            $job->update([
                'workflow_stage_id' => $targetStage->id,
            ]);

            // If moving to a final stage, mark as completed
            if ($targetStage->is_final && $job->completed_at === null) {
                $job->update(['completed_at' => now()]);
            }

            // If moving to initial stage, reset started_at and completed_at
            if ($targetStage->is_initial) {
                $job->update([
                    'started_at' => null,
                    'completed_at' => null,
                ]);
            }

            // Record the stage change event
            $this->recordEvent->stageChanged($job, $fromStage, $targetStage);

            return $job->fresh();
        });
    }
}
