<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EventType;
use App\Models\WorkflowEvent;
use App\Models\WorkflowJob;
use App\Models\WorkflowStage;
use Illuminate\Support\Facades\Auth;

final readonly class RecordWorkflowEventAction
{
    /**
     * Record a stage change event.
     */
    public function stageChanged(WorkflowJob $job, ?WorkflowStage $fromStage, WorkflowStage $toStage): WorkflowEvent
    {
        return WorkflowEvent::create([
            'workflow_job_id' => $job->id,
            'event_type' => EventType::StageChanged,
            'from_stage_id' => $fromStage?->id,
            'to_stage_id' => $toStage->id,
            'user_id' => Auth::id(),
        ]);
    }

    /**
     * Record a priority update event.
     */
    public function priorityUpdated(WorkflowJob $job, ?string $fromPriority, ?string $toPriority): WorkflowEvent
    {
        return WorkflowEvent::create([
            'workflow_job_id' => $job->id,
            'event_type' => EventType::PriorityUpdated,
            'user_id' => Auth::id(),
            'metadata' => [
                'from_priority' => $fromPriority,
                'to_priority' => $toPriority,
            ],
        ]);
    }

    /**
     * Record a note added event.
     */
    public function noteAdded(WorkflowJob $job): WorkflowEvent
    {
        return WorkflowEvent::create([
            'workflow_job_id' => $job->id,
            'event_type' => EventType::NoteAdded,
            'user_id' => Auth::id(),
        ]);
    }
}
