<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AutomationRun extends Model
{
    use BelongsToWorkspace;
    use HasUuids;

    /**
     * @return BelongsTo<Automation, $this>
     */
    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }

    /**
     * @return BelongsTo<AutomationVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(AutomationVersion::class, 'automation_version_id');
    }

    /**
     * @return HasMany<AutomationNodeRun, $this>
     */
    public function nodeRuns(): HasMany
    {
        return $this->hasMany(AutomationNodeRun::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'workspace_id' => 'integer',
            'automation_version_id' => 'integer',
            'pending_nodes' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
