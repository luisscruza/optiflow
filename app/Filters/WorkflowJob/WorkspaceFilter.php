<?php

declare(strict_types=1);

namespace App\Filters\WorkflowJob;

use App\Filters\QueryFilter;

final class WorkspaceFilter extends QueryFilter
{
    public function __construct(
        private readonly bool $showAllWorkspaces
    ) {}

    protected function shouldApply(): bool
    {
        return $this->showAllWorkspaces;
    }

    protected function apply($query)
    {
        return $query->withoutGlobalScope('workspace');
    }
}
