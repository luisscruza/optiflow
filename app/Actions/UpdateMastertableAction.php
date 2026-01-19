<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Mastertable;
use App\Models\User;

final readonly class UpdateMastertableAction
{
    /**
     * Update a mastertable.
     */
    public function handle(User $user, Mastertable $mastertable, array $data): Mastertable
    {
        $mastertable->update($data);

        return $mastertable->fresh();
    }
}
