<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WorkflowFieldType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
