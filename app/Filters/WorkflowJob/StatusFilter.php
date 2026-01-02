<?php

declare(strict_types=1);

namespace App\Filters\WorkflowJob;

use App\Filters\QueryFilter;

final class StatusFilter extends QueryFilter
{
    public function __construct(
        private readonly ?string $status
    ) {}

    protected function shouldApply(): bool
    {
        return $this->status !== null;
    }

    protected function apply($query)
    {
        return match ($this->status) {
            'pending' => $query->whereNull('completed_at')->whereNull('canceled_at'),
            'completed' => $query->whereNotNull('completed_at'),
            'canceled' => $query->whereNotNull('canceled_at'),
            default => $query,
        };
    }
}
