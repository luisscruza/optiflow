<?php

declare(strict_types=1);

namespace App\Filters\WorkflowJob;

use App\Filters\QueryFilter;

final class DateRangeFilter extends QueryFilter
{
    public function __construct(
        private readonly ?string $dateFrom,
        private readonly ?string $dateTo
    ) {}

    protected function shouldApply(): bool
    {
        return $this->dateFrom !== null || $this->dateTo !== null;
    }

    protected function apply($query)
    {
        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        return $query;
    }
}
