<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Mastertable;
use App\Models\User;

final readonly class DeleteMastertableAction
{
    /**
     * Delete a mastertable and all its items.
     */
    public function handle(User $user, Mastertable $mastertable): void
    {
        $mastertable->delete();
    }
}
