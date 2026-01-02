<?php

declare(strict_types=1);

namespace App\Filters\WorkflowJob;

use App\Filters\QueryFilter;

final class DueStatusFilter extends QueryFilter
{
    public function __construct(
        private readonly ?string $dueStatus
    ) {}

    protected function shouldApply(): bool
    {
        return $this->dueStatus !== null;
    }

    protected function apply($query)
    {
        return match ($this->dueStatus) {
            'overdue' => $query->whereNotNull('due_date')->where('due_date', '<', now()),
            'not_overdue' => $query->where(function ($q) {
                $q->whereNull('due_date')->orWhere('due_date', '>=', now());
            }),
            default => $query,
        };
    }
}
