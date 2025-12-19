<?php

declare(strict_types=1);

namespace App\Actions;

use App\Facades\Impersonator;
use App\Models\User;

final readonly class ImpersonateUserAction
{
    /**
     * Execute the action.
     */
    public function handle(User $impersonated): void
    {
        Impersonator::start($impersonated);
    }
}
