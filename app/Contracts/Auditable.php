<?php

declare(strict_types=1);

namespace App\Contracts;

use Spatie\Activitylog\LogOptions;

interface Auditable
{
    public function getActivitylogOptions(): LogOptions;

    /**
     * Get human-readable field names for activity log display.
     *
     * @return array<string, string>
     */
    public function getActivityFieldLabels(): array;
}
