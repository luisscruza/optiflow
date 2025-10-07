<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class CreateContactAction
{
    /**
     * Create a new contact.
     */
    public function handle(User $user, array $data): Contact
    {

        return DB::transaction(function () use ($data) {
            // Extract address data if present
            $addressData = $data['address'] ?? null;
            unset($data['address']);

            // Create the contact
            $contact = Contact::query()->create($data);

            // Create the address if provided
            if ($addressData && array_filter($addressData) !== []) {
                $contact->addresses()->create($addressData);
            }

            return $contact->load('addresses');
        });
    }
}
