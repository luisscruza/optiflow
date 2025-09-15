<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Models\User;

final readonly class UpdateContactAction
{
    /**
     * Update a contact.
     */
    public function handle(User $user, Contact $contact, array $data): Contact
    {
        $contact->update($data);

        return $contact->fresh();
    }
}
