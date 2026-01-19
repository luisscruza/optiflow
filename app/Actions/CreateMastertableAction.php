<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Mastertable;
use App\Models\User;

final readonly class CreateMastertableAction
{
    /**
     * Create a new mastertable.
     */
    public function handle(User $user, array $data): Mastertable
    {
        return Mastertable::query()->create($data);
    }
}
