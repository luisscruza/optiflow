<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WorkflowFieldType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $workflow_id
 * @property string $name
 * @property string $key
 * @property WorkflowFieldType $type
 * @property int|null $mastertable_id
 * @property bool $is_required
 * @property string|null $placeholder
 * @property string|null $default_value
 * @property int $position
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read Workflow $workflow
 * @property-read Mastertable|null $mastertable
 */
final class WorkflowField extends Model
{
    use HasUuids;

    /**
     * @return BelongsTo<Workflow, $this>
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * @return BelongsTo<Mastertable, $this>
     */
    public function mastertable(): BelongsTo
    {
        return $this->belongsTo(Mastertable::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => WorkflowFieldType::class,
            'mastertable_id' => 'integer',
            'is_required' => 'boolean',
            'position' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
