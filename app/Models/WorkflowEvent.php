<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WorkflowEvent extends Model
{
    use HasUuids;

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = ['event_type_label'];

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
     * Get the human-readable label for the event type.
     *
     * @return Attribute<string, never>
     */
    protected function eventTypeLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $eventType = $this->event_type;

            if ($eventType instanceof EventType) {
                return $eventType->label();
            }

            return EventType::tryFrom((string) $eventType)?->label() ?? (string) $eventType;
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => EventType::class,
            'user_id' => 'integer',
            'metadata' => 'array',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
