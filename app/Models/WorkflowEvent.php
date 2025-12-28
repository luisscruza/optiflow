<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WorkflowEvent extends Model
{
    /**
     * @return BelongsTo<WorkflowJob, $this>
     */
    public function workflowJob(): BelongsTo
    {
        return $this->belongsTo(WorkflowJob::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<WorkflowStage, $this>
     */
    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(WorkflowStage::class);
    }

    /**
     * @return BelongsTo<WorkflowStage, $this>
     */
    public function toStage(): BelongsTo
    {
        return $this->belongsTo(WorkflowStage::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'workflow_job_id' => 'integer',
            'from_stage_id' => 'integer',
            'to_stage_id' => 'integer',
            'event_type' => EventType::class,
            'user_id' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
