<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $automation_id
 * @property int $automation_version_id
 * @property int $workspace_id
 * @property string $trigger_event_key
 * @property string $subject_type
 * @property string $subject_id
 * @property string $status
 * @property int $pending_nodes
 * @property \Carbon\CarbonImmutable|null $started_at
 * @property \Carbon\CarbonImmutable|null $finished_at
 * @property string|null $error
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read Automation $automation
 * @property-read AutomationVersion $version
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AutomationNodeRun> $nodeRuns
 * @property-read int|null $node_runs_count
 */
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
