<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class UpdateContactAction
{
    /**
     * Update a contact.
     */
    public function handle(User $user, Contact $contact, array $data): Contact
    {
        return DB::transaction(function () use ($contact, $data) {
            // Extract address data if present
            $addressData = $data['address'] ?? null;
            unset($data['address']);

            // Update the contact
            $contact->update($data);

            // Update or create the primary address if provided
            if ($addressData && ! empty(array_filter($addressData))) {
                $primaryAddress = $contact->addresses()->first();

                if ($primaryAddress) {
                    $primaryAddress->update($addressData);
                } else {
                    $contact->addresses()->create($addressData);
                }
            }

            return $contact->fresh('addresses');
        });
    }
}
