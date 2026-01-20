<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $automation_run_id
 * @property string $node_id
 * @property string $node_type
 * @property string $status
 * @property int $attempts
 * @property array<array-key, mixed>|null $input
 * @property array<array-key, mixed>|null $output
 * @property string|null $error
 * @property \Carbon\CarbonImmutable|null $started_at
 * @property \Carbon\CarbonImmutable|null $finished_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read AutomationRun $run
 */
final class AutomationNodeRun extends Model
{
    use HasUuids;

    /**
     * @return BelongsTo<AutomationRun, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(AutomationRun::class, 'automation_run_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'input' => 'array',
            'output' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
