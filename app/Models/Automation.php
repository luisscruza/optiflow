<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Automation extends Model
{
    use BelongsToWorkspace;
    use HasUuids;

    /**
     * @return HasMany<AutomationVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(AutomationVersion::class);
    }

    /**
     * @return HasMany<AutomationTrigger, $this>
     */
    public function triggers(): HasMany
    {
        return $this->hasMany(AutomationTrigger::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'workspace_id' => 'integer',
            'is_active' => 'boolean',
            'published_version' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
