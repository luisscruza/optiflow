<?php

declare(strict_types=1);

namespace App\Filters\WorkflowJob;

use App\Filters\QueryFilter;

final class PriorityFilter extends QueryFilter
{
    public function __construct(
        private readonly ?string $priority
    ) {}

    protected function shouldApply(): bool
    {
        return $this->priority !== null;
    }

    protected function apply($query)
    {
        return $query->where('priority', $this->priority);
    }
}
