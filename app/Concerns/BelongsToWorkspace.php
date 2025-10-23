<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToWorkspace
{
    /**
     * Get the workspace that owns the model.
     *
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Check if the model belongs to the current user's workspace.
     */
    public function belongsToCurrentWorkspace(): bool
    {
        return Auth::check()
            && Auth::user()->current_workspace_id
            && $this->workspace_id === Auth::user()->current_workspace_id;
    }

    /**
     * Check if the model belongs to a specific workspace.
     */
    public function belongsToWorkspace(int|Workspace $workspace): bool
    {
        $workspaceId = $workspace instanceof Workspace ? $workspace->id : $workspace;

        return $this->workspace_id === $workspaceId;
    }

    /**
     * Boot the trait.
     */
    protected static function bootBelongsToWorkspace(): void
    {
        static::addGlobalScope('workspace', function (Builder $builder): void {
            if (Auth::check() && Auth::user()->current_workspace_id) {
                $builder->where('workspace_id', Auth::user()->current_workspace_id);
            }
        });

        static::creating(function ($model): void {
            if (! $model->workspace_id && Auth::check() && Auth::user()->current_workspace_id) {
                $model->workspace_id = Auth::user()->current_workspace_id;
            }
        });
    }

    /**
     * Scope a query to a specific workspace.
     */
    protected function scopeForWorkspace(Builder $query, int|Workspace $workspace): Builder
    {
        $workspaceId = $workspace instanceof Workspace ? $workspace->id : $workspace;

        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope a query without the global workspace scope.
     */
    protected function scopeWithoutWorkspaceScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('workspace');
    }
}
