<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Models\User;

final readonly class CreateContactAction
{
    /**
     * Create a new contact.
     */
    public function handle(User $user, array $data): Contact
    {
        $workspace = $user->currentWorkspace();

        return $workspace->contacts()->create($data);
    }
}
