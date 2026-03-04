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
            // Extract nested data if present
            $addressData = $data['address'] ?? null;
            $relationships = $data['relationships'] ?? [];
            unset($data['address'], $data['relationships']);

            // Create the contact
            $contact = Contact::query()->create($data);

            // Create the address if provided
            if ($addressData && array_filter($addressData) !== []) {
                $contact->addresses()->create($addressData);
            }

            $this->syncRelationships($contact, $relationships);

            return $contact->load(['addresses', 'relationships']);
        });
    }

    /**
     * @param array<int, array{related_contact_id:int, description?:string|null}> $relationships
     */
    private function syncRelationships(Contact $contact, array $relationships): void
    {
        $syncData = [];

        foreach ($relationships as $relationship) {
            $relatedContactId = (int) ($relationship['related_contact_id'] ?? 0);
            if ($relatedContactId <= 0 || $relatedContactId === $contact->id) {
                continue;
            }

            $description = isset($relationship['description']) ? trim((string) $relationship['description']) : null;
            $syncData[$relatedContactId] = ['description' => $description !== '' ? $description : null];
        }

        if ($syncData === []) {
            return;
        }

        $contact->relationships()->sync($syncData);

        foreach ($syncData as $relatedContactId => $pivotData) {
            $relatedContact = Contact::query()->find((int) $relatedContactId);
            if (! $relatedContact) {
                continue;
            }

            $reverseData = $relatedContact->relationships()
                ->where('contacts.id', $contact->id)
                ->first()
                ? [$contact->id => $pivotData]
                : [$contact->id => $pivotData];

            $relatedContact->relationships()->syncWithoutDetaching($reverseData);
        }
    }
}
