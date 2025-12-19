<?php

declare(strict_types=1);

namespace App\Actions;

use App\Facades\Impersonator;

final readonly class StopImpersonatingUserAction
{
    /**
     * Execute the action.
     */
    public function handle(): void
    {
        Impersonator::stop();
    }
}
