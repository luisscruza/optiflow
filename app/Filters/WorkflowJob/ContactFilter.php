<?php

declare(strict_types=1);

namespace App\Filters\WorkflowJob;

use App\Filters\QueryFilter;

final class ContactFilter extends QueryFilter
{
    public function __construct(
        private readonly ?string $contactId
    ) {}

    protected function shouldApply(): bool
    {
        return $this->contactId !== null;
    }

    protected function apply($query)
    {
        return $query->where('contact_id', $this->contactId);
    }
}
