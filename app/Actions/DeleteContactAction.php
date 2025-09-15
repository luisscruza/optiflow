<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Models\User;

final readonly class DeleteContactAction
{
    /**
     * Delete a contact.
     */
    public function handle(User $user, Contact $contact): void
    {
        $contact->delete();
    }
}
