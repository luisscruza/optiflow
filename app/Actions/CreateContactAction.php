<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;

final readonly class CreateContactAction
{
    /**
     * Create a new contact.
     */
    public function handle(User $user, array $data): Contact
    {
        $workspace = Context::get('workspace');

        return DB::transaction(function () use ($workspace, $data) {
            // Extract address data if present
            $addressData = $data['address'] ?? null;
            unset($data['address']);

            // Create the contact
            $contact = $workspace->contacts()->create($data);

            // Create the address if provided
            if ($addressData && array_filter($addressData) !== []) {
                $contact->addresses()->create($addressData);
            }

            return $contact->load('addresses');
        });
    }
}
