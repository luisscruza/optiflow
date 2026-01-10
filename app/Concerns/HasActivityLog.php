<?php

declare(strict_types=1);

namespace App\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait HasActivityLog
{
    use LogsActivity;

    /**
     * Get the activity log options for this model.
     */
    abstract public function getActivitylogOptions(): LogOptions;
}
